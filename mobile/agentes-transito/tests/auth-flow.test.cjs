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

const withToken = async () => "token-de-prueba";
const withoutToken = async () => null;

test("guarda y restaura únicamente el perfil, preservando boletas", async () => {
  const storage = createMemoryStorage({ "@agentes_transito_infracciones_v1": "boletas" });
  await storageApi.saveAgentProfile(storage, profile);
  const result = await storageApi.restoreAgentProfile(storage, async () => {}, withToken);

  assert.deepEqual(result.profile, profile);
  assert.deepEqual(result.errors, []);
  assert.equal(storage.values.get("@agentes_transito_infracciones_v1"), "boletas");
  assert.equal(storage.values.has(storageApi.LEGACY_AUTH_STORAGE_KEY), false);
});

test("no restaura la clave antigua que pudo contener identidades simuladas", async () => {
  const storage = createMemoryStorage({
    [storageApi.LEGACY_AUTH_STORAGE_KEY]: JSON.stringify(profile),
  });
  const result = await storageApi.restoreAgentProfile(storage, async () => {}, withToken);

  assert.equal(result.profile, null);
  assert.equal(storage.values.has(storageApi.LEGACY_AUTH_STORAGE_KEY), false);
});

test("sin token guardado no restaura el perfil y pide iniciar sesión sin tocar boletas", async () => {
  const storage = createMemoryStorage({
    [storageApi.AUTH_PROFILE_STORAGE_KEY]: JSON.stringify(profile),
    "@agentes_transito_infracciones_v1": "boletas",
  });
  const result = await storageApi.restoreAgentProfile(storage, async () => {}, withoutToken);

  assert.equal(result.profile, null);
  assert.equal(storage.values.has(storageApi.AUTH_PROFILE_STORAGE_KEY), false);
  assert.equal(storage.values.get("@agentes_transito_infracciones_v1"), "boletas");
  assert.match(result.errors.join(" "), /Inicia sesión nuevamente/);
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
  const result = await storageApi.restoreAgentProfile(storage, async () => {}, withToken);

  assert.equal(result.profile, null);
  assert.equal(storage.values.has(storageApi.AUTH_PROFILE_STORAGE_KEY), false);
  assert.match(result.errors.join(" "), /perfil local no es válido/);
});

test("cierre limpia solo el perfil y la credencial de acceso", async () => {
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

function loadClient(secureStore, fetchImpl) {
  const previousFetch = globalThis.fetch;
  globalThis.fetch = fetchImpl;
  const client = loadTypeScript("src/api/client.ts", {
    "expo-secure-store": secureStore,
    "react-native": { Platform: { OS: "ios" } },
    "../constants/config": { API_BASE_URL: "http://localhost/api" },
  });
  return { client, restore: () => (globalThis.fetch = previousFetch) };
}

function createSecureStore() {
  const values = new Map();
  return {
    values,
    getItemAsync: async (key) => (values.has(key) ? values.get(key) : null),
    setItemAsync: async (key, value) => values.set(key, value),
    deleteItemAsync: async (key) => values.delete(key),
  };
}

test("en nativo guarda el token en SecureStore y lo envía como Bearer", async () => {
  const secureStore = createSecureStore();
  let sentHeaders;
  const { client, restore } = loadClient(secureStore, async (_url, init) => {
    sentHeaders = init.headers;
    return {
      ok: true,
      status: 200,
      headers: { get: () => "application/json" },
      json: async () => ({ success: true }),
    };
  });
  try {
    await client.saveAgentToken("abc123");
    await client.apiRequest("/api/infracciones/sincronizar", { method: "POST", body: {} });
    assert.equal(sentHeaders.Authorization, "Bearer abc123");

    await client.clearAgentToken();
    assert.equal(await client.getAgentToken(), null);
  } finally {
    restore();
  }
});

test("una respuesta 401 se expone como ApiError con estado 401", async () => {
  const { client, restore } = loadClient(createSecureStore(), async () => ({
    ok: false,
    status: 401,
    statusText: "Unauthorized",
    headers: { get: () => "application/json" },
    json: async () => ({ success: false }),
  }));
  try {
    await assert.rejects(
      client.apiRequest("/api/infracciones/sincronizar", { method: "POST", body: {} }),
      (error) => error instanceof client.ApiError && error.status === 401
    );
  } finally {
    restore();
  }
});

function createLoginModule(apiRequest) {
  class ApiError extends Error {
    constructor(status) {
      super("HTTP error");
      this.status = status;
    }
  }
  const asyncStorage = { getItem: async () => null, setItem: async () => {}, removeItem: async () => {} };
  const supabaseClient = {
    from() {
      throw new Error("La app no debe escribir boletas directamente en Supabase.");
    },
    storage: {
      from() {
        throw new Error("La app no debe subir evidencias directamente a Supabase.");
      },
    },
  };
  const service = loadTypeScript("src/services/supabase.ts", {
    "@supabase/supabase-js": { createClient: () => supabaseClient },
    "@react-native-async-storage/async-storage": { __esModule: true, default: asyncStorage },
    "../api/client": { ApiError, apiRequest },
  });
  return { service, ApiError };
}

test("login devuelve perfil y token cuando el endpoint responde con éxito", async () => {
  let receivedOptions;
  const { service } = createLoginModule(async (_path, options) => {
    receivedOptions = options;
    return {
      success: true,
      token: "f".repeat(64),
      expira_en: "2026-10-16 23:00:00",
      agente: { id: 14, placa: "AGT-TEST", nombre_completo: "Agente de prueba" },
    };
  });

  const result = await service.loginAgente("agt-test", "clave-ficticia");

  assert.equal(result.agente.placa, "AGT-TEST");
  assert.equal(result.token, "f".repeat(64));
  assert.equal(result.expiraEn, "2026-10-16 23:00:00");
  assert.equal(receivedOptions.auth, false);
  assert.equal(receivedOptions.body.placa, "AGT-TEST");
});

test("login sin token en la respuesta no produce sesión", async () => {
  const { service } = createLoginModule(async () => ({
    success: true,
    agente: { id: 14, placa: "AGT-TEST", nombre_completo: "Agente de prueba" },
  }));

  await assert.rejects(service.loginAgente("AGT-TEST", "clave-ficticia"), /respuesta de autenticación incompleta/);
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

test("sincronizarBoleta propaga el 401 y no recurre a Supabase", async () => {
  const { service, ApiError } = createLoginModule(async () => {
    throw new ApiError(401);
  });

  await assert.rejects(
    service.sincronizarBoleta({ folio: "F-1", agente_placa: "AGT-TEST" }),
    (error) => error instanceof ApiError && error.status === 401
  );
});

test("sincronizarBoleta envía la boleta autenticada", async () => {
  let received;
  const { service } = createLoginModule(async (path, options) => {
    received = { path, options };
    return { success: true };
  });

  await service.sincronizarBoleta({ folio: "F-1", agente_placa: "AGT-TEST" });

  assert.equal(received.path, "/api/infracciones/sincronizar");
  assert.equal(received.options.auth, true);
});

function boletaPendiente(id) {
  return {
    id,
    folio: `F-${id}`,
    estado: "pendiente",
    agente: { placa: "AGT-TEST" },
    evidencias: { fotoPlaca: null, fotoContexto: null, fotoDocumento: null },
  };
}

function loadSync({ boletas, sincronizarBoleta }) {
  class ApiError extends Error {
    constructor(status) {
      super("HTTP error");
      this.status = status;
    }
  }
  const store = { boletas, ultimaSync: null };
  const sync = loadTypeScript("src/services/sync.ts", {
    "../api/client": { ApiError },
    "./storage": {
      obtenerInfraccionesLocales: async () => store.boletas,
      actualizarInfraccionesLocales: async (lista) => {
        store.boletas = lista;
      },
      registrarUltimaSincronizacion: async () => {
        store.ultimaSync = "ahora";
      },
    },
    "./supabase": {
      subirFotoEvidencia: async () => null,
      transformarInfraccionABoleta: (inf) => ({ folio: inf.folio }),
      sincronizarBoleta: (boleta) => sincronizarBoleta(boleta, ApiError),
    },
  });
  return { sync, store };
}

test("un 401 al sincronizar conserva las boletas pendientes y pide volver a iniciar sesión", async () => {
  const enviados = [];
  const { sync, store } = loadSync({
    boletas: [boletaPendiente("1"), boletaPendiente("2")],
    sincronizarBoleta: async (boleta, ApiError) => {
      enviados.push(boleta.folio);
      throw new ApiError(401);
    },
  });

  const result = await sync.sincronizarInfraccionesLocales();

  assert.equal(result.requiereLogin, true);
  assert.equal(result.exito, false);
  assert.match(result.mensaje, /Inicia sesión nuevamente/);
  // Se detiene en el primer 401 en lugar de reintentar cada boleta.
  assert.deepEqual(enviados, ["F-1"]);
  assert.deepEqual(store.boletas.map((b) => b.estado), ["pendiente", "pendiente"]);
  assert.equal(store.ultimaSync, null);
});

test("las boletas aceptadas antes del 401 quedan sincronizadas y el resto pendiente", async () => {
  const { sync, store } = loadSync({
    boletas: [boletaPendiente("1"), boletaPendiente("2")],
    sincronizarBoleta: async (boleta, ApiError) => {
      if (boleta.folio === "F-2") throw new ApiError(401);
    },
  });

  const result = await sync.sincronizarInfraccionesLocales();

  assert.equal(result.requiereLogin, true);
  assert.equal(result.totalSincronizadas, 1);
  assert.deepEqual(store.boletas.map((b) => b.estado), ["sincronizada", "pendiente"]);
});

test("otros errores no piden iniciar sesión", async () => {
  const { sync, store } = loadSync({
    boletas: [boletaPendiente("1")],
    sincronizarBoleta: async () => {
      throw new TypeError("network error");
    },
  });
  const originalError = console.error;
  console.error = () => {};
  try {
    const result = await sync.sincronizarInfraccionesLocales();
    assert.equal(result.requiereLogin, undefined);
    assert.equal(result.fallidos, 1);
    assert.equal(store.boletas[0].estado, "pendiente");
  } finally {
    console.error = originalError;
  }
});
