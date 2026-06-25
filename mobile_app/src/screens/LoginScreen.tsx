import React, { useState } from 'react';
import {
  View, StyleSheet, TextInput, TouchableOpacity, Text, KeyboardAvoidingView, Platform, Alert, Image
} from 'react-native';
import { AuthService } from '../services/ApiService';
import ApiService from '../services/ApiService';
import AsyncStorage from '@react-native-async-storage/async-storage';

interface Props {
  onLogin: () => void;
}

export default function LoginScreen({ onLogin }: Props) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);

  const handleLogin = async () => {
    if (!email.trim() || !password.trim()) {
      Alert.alert('Error', 'Por favor ingresa tu email y contraseña');
      return;
    }

    setLoading(true);
    try {
      const response = await AuthService.login(email, password);
      if (response.data.success) {
        await AsyncStorage.setItem('auth_token', response.data.data.token);
        await AsyncStorage.setItem('user', JSON.stringify(response.data.data));
        ApiService.setToken(response.data.data.token);
        onLogin();
      } else {
        Alert.alert('Error', response.data.message || 'Credenciales inválidas');
      }
    } catch (error: any) {
      Alert.alert('Error de conexión', 'No se pudo conectar con el servidor. Verifica tu conexión.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <KeyboardAvoidingView style={styles.container} behavior={Platform.OS === 'ios' ? 'padding' : 'height'}>
      <View style={styles.logoContainer}>
        <Text style={styles.logoText}>EstrateGIA</Text>
        <Text style={styles.subtitle}>Gestión de Planeación Estratégica</Text>
        <Text style={styles.poweredBy}>con Inteligencia Artificial</Text>
      </View>

      <View style={styles.form}>
        <TextInput
          style={styles.input}
          placeholder="Email"
          value={email}
          onChangeText={setEmail}
          keyboardType="email-address"
          autoCapitalize="none"
          placeholderTextColor="#999"
        />
        <TextInput
          style={styles.input}
          placeholder="Contraseña"
          value={password}
          onChangeText={setPassword}
          secureTextEntry
          placeholderTextColor="#999"
        />
        <TouchableOpacity
          style={[styles.button, loading && styles.buttonDisabled]}
          onPress={handleLogin}
          disabled={loading}
        >
          <Text style={styles.buttonText}>
            {loading ? 'Iniciando sesión...' : 'Ingresar'}
          </Text>
        </TouchableOpacity>
      </View>

      <Text style={styles.footer}>v1.0.0 - Mayo 2026</Text>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f0f4ff',
    justifyContent: 'center',
    paddingHorizontal: 32,
  },
  logoContainer: {
    alignItems: 'center',
    marginBottom: 48,
  },
  logoText: {
    fontSize: 42,
    fontWeight: 'bold',
    color: '#1a73e8',
    letterSpacing: 2,
  },
  subtitle: {
    fontSize: 16,
    color: '#5f6368',
    marginTop: 8,
  },
  poweredBy: {
    fontSize: 14,
    color: '#6f42c1',
    marginTop: 4,
    fontStyle: 'italic',
  },
  form: {
    gap: 16,
  },
  input: {
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: '#dde',
    borderRadius: 12,
    padding: 16,
    fontSize: 16,
    color: '#333',
  },
  button: {
    backgroundColor: '#1a73e8',
    borderRadius: 12,
    padding: 16,
    alignItems: 'center',
    marginTop: 8,
  },
  buttonDisabled: {
    opacity: 0.6,
  },
  buttonText: {
    color: '#fff',
    fontSize: 18,
    fontWeight: '600',
  },
  footer: {
    textAlign: 'center',
    color: '#999',
    marginTop: 48,
    fontSize: 12,
  },
});
