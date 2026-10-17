const assert = require("node:assert/strict");
const fs = require("node:fs");
const Module = require("node:module");
const path = require("node:path");
const test = require("node:test");
const ts = require("typescript");

const appRoot = path.resolve(__dirname, "..");

function loadTypeScript(relativePath, mocks = {}) {
  const filename = path.join(appRoot, relativePath);
  const source = fs.readFileSync(filename, "utf8");
  const javascript = ts.transpileModule(source, {
    compilerOptions: {
      module: ts.ModuleKind.CommonJS,
      target: ts.ScriptTarget.ES2020,
    },
  }).outputText;
  const loaded = new Module(filename, module);
  loaded.filename = filename;
  loaded.paths = Module._nodeModulePaths(path.dirname(filename));
  loaded.require = (request) => {
    if (Object.hasOwn(mocks, request)) return mocks[request];
    return Module.createRequire(filename)(request);
  };
  loaded._compile(javascript, filename);
  return loaded.exports;
}

function createMemoryStorage(initial = {}) {
  const values = new Map(Object.entries(initial));
  return {
    values,
    async getItem(key) {
      return values.has(key) ? values.get(key) : null;
    },
    async setItem(key, value) {
      values.set(key, value);
    },
    async removeItem(key) {
      values.delete(key);
    },
  };
}

const storageApi = loadTypeScript("src/services/authProfileStorage.ts");
const profile = {
  id: 14,
  placa: "AGT-TEST",
  nombre: "Agente de prueba",
  rol: "Agente Vial Operativo",
  sector: "Sector Norte",
};

test("guarda y restaura únicamente el perfil, preservando boletas", async () => {
  const storage = createMemoryStorage({ "@agentes_transito_infracciones_v1": "boletas" });
  await storageApi.saveAgentProfile(storage, profile);
  const result = await storageApi.restoreAgentProfile(storage, async () => {});

  assert.deepEqual(result.profile, profile);
  assert.deepEqual(result.errors, []);
  assert.equal(storage.values.get("@agentes_transito_infracciones_v1"), "boletas");
  assert.equal(storage.values.has(storageApi.LEGACY_AUTH_STORAGE_KEY), false);
});

test("no restaura la clave antigua que pudo contener identidades simuladas", async () => {
  const storage = createMemoryStorage({
    [storageApi.LEGACY_AUTH_STORAGE_KEY]: JSON.stringify(profile),
  });
  const result = await storageApi.restoreAgentProfile(storage, async () => {});

  assert.equal(result.profile, null);
  assert.equal(storage.values.has(storageApi.LEGACY_AUTH_STORAGE_KEY), false);
});

test("un error al guardar el perfil se propaga", async () => {
  const storage = createMemoryStorage();
  storage.setItem = async () => {
    throw new Error("storage unavailable");
  };

  await assert.rejects(storageApi.saveAgentProfile(storage, profile), /storage unavailable/);
});

test("rechaza perfiles locales mal formados", async () => {
  const storage = createMemoryStorage({
    [storageApi.AUTH_PROFILE_STORAGE_KEY]: JSON.stringify({ id: 14, placa: "AGT-TEST" }),
  });
  const result = await storageApi.restoreAgentProfile(storage, async () => {});

  assert.equal(result.profile, null);
  assert.equal(storage.values.has(storageApi.AUTH_PROFILE_STORAGE_KEY), false);
  assert.match(result.errors.join(" "), /perfil local no es válido/);
});

test("cierre limpia solo el perfil y la credencial obsoleta", async () => {
  const storage = createMemoryStorage({
    [storageApi.AUTH_PROFILE_STORAGE_KEY]: JSON.stringify(profile),
    [storageApi.LEGACY_AUTH_STORAGE_KEY]: JSON.stringify(profile),
    "@agentes_transito_infracciones_v1": "boletas",
  });
  let credentialCleared = false;
  const errors = await storageApi.clearAgentProfile(storage, async () => {
    credentialCleared = true;
  });

  assert.deepEqual(errors, []);
  assert.equal(credentialCleared, true);
  assert.equal(storage.values.has(storageApi.AUTH_PROFILE_STORAGE_KEY), false);
  assert.equal(storage.values.has(storageApi.LEGACY_AUTH_STORAGE_KEY), false);
  assert.equal(storage.values.get("@agentes_transito_infracciones_v1"), "boletas");
});

test("limpieza en web no llama SecureStore y borra solo el token antiguo", async () => {
  const removed = [];
  const previousLocalStorage = Object.getOwnPropertyDescriptor(globalThis, "localStorage");
  Object.defineProperty(globalThis, "localStorage", {
    configurable: true,
    value: { removeItem: (key) => removed.push(key) },
  });
  let secureStoreTouched = false;
  try {
    const client = loadTypeScript("src/api/client.ts", {
      "expo-secure-store": {
        getItemAsync: async () => {
          secureStoreTouched = true;
          return null;
        },
        deleteItemAsync: async () => {
          secureStoreTouched = true;
        },
      },
      "react-native": { Platform: { OS: "web" } },
      "../constants/config": { API_BASE_URL: "http://localhost/api" },
    });

    await client.clearObsoleteAgentToken();
  } finally {
    if (previousLocalStorage) {
      Object.defineProperty(globalThis, "localStorage", previousLocalStorage);
    } else {
      delete globalThis.localStorage;
    }
  }

  assert.deepEqual(removed, ["auth_token"]);
  assert.equal(secureStoreTouched, false);
});

function createLoginModule(apiRequest) {
  class ApiError extends Error {
    constructor(status) {
      super("HTTP error");
      this.status = status;
    }
  }
  const asyncStorage = { getItem: async () => null, setItem: async () => {}, removeItem: async () => {} };
  const service = loadTypeScript("src/services/supabase.ts", {
    "@supabase/supabase-js": { createClient: () => ({}) },
    "@react-native-async-storage/async-storage": { __esModule: true, default: asyncStorage },
    "../api/client": { ApiError, apiRequest },
  });
  return { service, ApiError };
}

test("login acepta perfil solo si el endpoint responde con éxito y no requiere token", async () => {
  let receivedOptions;
  const { service } = createLoginModule(async (_path, options) => {
    receivedOptions = options;
    return { success: true, agente: { id: 14, placa: "AGT-TEST", nombre_completo: "Agente de prueba" } };
  });

  const result = await service.loginAgente("agt-test", "clave-ficticia");

  assert.equal(result.placa, "AGT-TEST");
  assert.equal(receivedOptions.auth, false);
  assert.equal(receivedOptions.body.placa, "AGT-TEST");
});

test("rechazo 401 no produce identidad local", async () => {
  const { service, ApiError } = createLoginModule(async () => {
    throw new ApiError(401);
  });

  await assert.rejects(service.loginAgente("AGT-UNKNOWN", "clave-ficticia"), /Credenciales incorrectas/);
});

test("fallo de red no produce identidad fabricada", async () => {
  const { service } = createLoginModule(async () => {
    throw new TypeError("network error");
  });

  await assert.rejects(service.loginAgente("AGT-UNKNOWN", "clave-ficticia"), /No se pudo conectar/);
});
