import React, { useState, useEffect } from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createStackNavigator } from '@react-navigation/stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { Provider as PaperProvider } from 'react-native-paper';
import AsyncStorage from '@react-native-async-storage/async-storage';
import Icon from 'react-native-vector-icons/MaterialCommunityIcons';

import LoginScreen from './screens/LoginScreen';
import DashboardScreen from './screens/DashboardScreen';
import PlaneacionScreen from './screens/PlaneacionScreen';
import ProcesosScreen from './screens/ProcesosScreen';
import IndicadoresScreen from './screens/IndicadoresScreen';
import DocumentosScreen from './screens/DocumentosScreen';
import ActividadesScreen from './screens/ActividadesScreen';
import EvaluacionScreen from './screens/EvaluacionScreen';
import IAAssistantScreen from './screens/IAAssistantScreen';
import ApiService from './services/ApiService';

const Stack = createStackNavigator();
const Tab = createBottomTabNavigator();

const theme = {
  colors: {
    primary: '#1a73e8',
    secondary: '#5f6368',
    success: '#28a745',
    warning: '#ffc107',
    danger: '#dc3545',
    purple: '#6f42c1',
    background: '#f8f9fa',
    surface: '#ffffff',
  },
};

function TabNavigator() {
  return (
    <Tab.Navigator
      screenOptions={({ route }) => ({
        tabBarIcon: ({ color, size }) => {
          const icons: { [key: string]: string } = {
            Dashboard: 'view-dashboard',
            Planeacion: 'bullseye-arrow',
            Procesos: 'sitemap',
            Indicadores: 'gauge',
            Actividades: 'clipboard-check',
          };
          return <Icon name={icons[route.name] || 'circle'} size={size} color={color} />;
        },
        tabBarActiveTintColor: theme.colors.primary,
        tabBarInactiveTintColor: theme.colors.secondary,
        headerStyle: { backgroundColor: theme.colors.primary },
        headerTintColor: '#fff',
      })}
    >
      <Tab.Screen name="Dashboard" component={DashboardScreen} options={{ title: 'Panel' }} />
      <Tab.Screen name="Planeacion" component={PlaneacionScreen} options={{ title: 'Planeación' }} />
      <Tab.Screen name="Procesos" component={ProcesosScreen} options={{ title: 'Procesos' }} />
      <Tab.Screen name="Indicadores" component={IndicadoresScreen} options={{ title: 'KPIs' }} />
      <Tab.Screen name="Actividades" component={ActividadesScreen} options={{ title: 'Mis Tareas' }} />
    </Tab.Navigator>
  );
}

export default function App() {
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    checkAuth();
  }, []);

  const checkAuth = async () => {
    try {
      const token = await AsyncStorage.getItem('auth_token');
      if (token) {
        ApiService.setToken(token);
        const response = await ApiService.get('/auth/me');
        if (response.data.success) {
          setIsAuthenticated(true);
        }
      }
    } catch (error) {
      await AsyncStorage.removeItem('auth_token');
    } finally {
      setIsLoading(false);
    }
  };

  const handleLogin = async () => {
    setIsAuthenticated(true);
  };

  const handleLogout = async () => {
    await AsyncStorage.removeItem('auth_token');
    ApiService.clearToken();
    setIsAuthenticated(false);
  };

  if (isLoading) return null;

  return (
    <PaperProvider>
      <NavigationContainer>
        <Stack.Navigator>
          {!isAuthenticated ? (
            <Stack.Screen name="Login" options={{ headerShown: false }}>
              {(props) => <LoginScreen {...props} onLogin={handleLogin} />}
            </Stack.Screen>
          ) : (
            <>
              <Stack.Screen name="Main" options={{ headerShown: false }} component={TabNavigator} />
              <Stack.Screen name="Documentos" component={DocumentosScreen}
                options={{ title: 'Documentos ISO', headerStyle: { backgroundColor: theme.colors.primary }, headerTintColor: '#fff' }} />
              <Stack.Screen name="Evaluacion" component={EvaluacionScreen}
                options={{ title: 'Mi Evaluación', headerStyle: { backgroundColor: theme.colors.primary }, headerTintColor: '#fff' }} />
              <Stack.Screen name="IAAsistente" component={IAAssistantScreen}
                options={{ title: 'Asistente IA', headerStyle: { backgroundColor: theme.colors.purple }, headerTintColor: '#fff' }} />
            </>
          )}
        </Stack.Navigator>
      </NavigationContainer>
    </PaperProvider>
  );
}
