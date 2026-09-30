import React from "react";
import { Stack } from "expo-router";
import { StatusBar } from "expo-status-bar";
import { SafeAreaProvider } from "react-native-safe-area-context";
import { AuthProvider } from "../src/contexts/AuthContext";
import { InfraccionesProvider } from "../src/contexts/InfraccionesContext";

export default function RootLayout() {
  return (
    <SafeAreaProvider>
      <AuthProvider>
        <InfraccionesProvider>
          <StatusBar style="light" />
          <Stack
            screenOptions={{
              headerShown: false,
              animation: "slide_from_right",
              contentStyle: {
                backgroundColor: "#09090b",
              },
            }}
          >
            <Stack.Screen name="index" options={{ title: "Acceso Agentes" }} />
            <Stack.Screen name="dashboard" options={{ title: "Panel de Control" }} />
            <Stack.Screen name="nueva-infraccion" options={{ title: "Nueva Boleta de Infracción" }} />
          </Stack>
        </InfraccionesProvider>
      </AuthProvider>
    </SafeAreaProvider>
  );
}
