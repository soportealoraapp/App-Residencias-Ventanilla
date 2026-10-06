import React, { useEffect } from "react";
import { Stack } from "expo-router";
import { StatusBar } from "expo-status-bar";
import { Platform } from "react-native";
import { SafeAreaProvider } from "react-native-safe-area-context";
import { useFonts } from "expo-font";
import { Ionicons } from "@expo/vector-icons";
import { AuthProvider } from "../src/contexts/AuthContext";
import { InfraccionesProvider } from "../src/contexts/InfraccionesContext";
import { ThemeProvider, useTheme } from "../src/contexts/ThemeContext";

function AppNavigation() {
  const { colors, isDark } = useTheme();

  return (
    <>
      <StatusBar style={isDark ? "light" : "dark"} />
      <Stack
        screenOptions={{
          headerShown: false,
          animation: "slide_from_right",
          contentStyle: {
            backgroundColor: colors.background,
          },
        }}
      >
        <Stack.Screen name="index" options={{ title: "Acceso Agentes" }} />
        <Stack.Screen name="login" options={{ title: "Iniciar sesión" }} />
        <Stack.Screen name="registro" options={{ title: "Crear una cuenta" }} />
        <Stack.Screen name="dashboard" options={{ title: "Panel de Control" }} />
        <Stack.Screen name="nueva-infraccion" options={{ title: "Nueva Boleta de Infracción" }} />
      </Stack>
    </>
  );
}

export default function RootLayout() {
  const [fontsLoaded] = useFonts({
    ...Ionicons.font,
  });

  useEffect(() => {
    if (Platform.OS === "web" && typeof document !== "undefined") {
      const styleId = "expo-vector-icons-ionicons-web";
      if (!document.getElementById(styleId)) {
        const fontStyle = document.createElement("style");
        fontStyle.id = styleId;
        fontStyle.type = "text/css";
        fontStyle.appendChild(
          document.createTextNode(`
            @font-face {
              font-family: 'Ionicons';
              src: url('https://cdn.jsdelivr.net/npm/react-native-vector-icons@10.2.0/Fonts/Ionicons.ttf') format('truetype');
              font-display: swap;
            }
          `)
        );
        document.head.appendChild(fontStyle);
      }
    }
  }, []);

  return (
    <SafeAreaProvider>
      <ThemeProvider>
        <AuthProvider>
          <InfraccionesProvider>
            <AppNavigation />
          </InfraccionesProvider>
        </AuthProvider>
      </ThemeProvider>
    </SafeAreaProvider>
  );
}
