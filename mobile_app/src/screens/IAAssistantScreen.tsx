import React, { useState, useRef } from 'react';
import { View, StyleSheet, TextInput, FlatList, Text, TouchableOpacity, KeyboardAvoidingView, Platform } from 'react-native';
import Icon from 'react-native-vector-icons/MaterialCommunityIcons';
import { IAService } from '../services/ApiService';

interface Message {
  id: number;
  role: 'user' | 'assistant';
  content: string;
  timestamp: string;
}

export default function IAAssistantScreen() {
  const [messages, setMessages] = useState<Message[]>([
    {
      id: 0, role: 'assistant',
      content: 'Hola, soy EstrateGIA, tu asistente de planeación estratégica con IA. Puedo ayudarte a:\n\n• Definir objetivos y estrategias\n• Analizar tu FODA\n• Recomendar indicadores KPIs\n• Generar documentación ISO\n• Predecir tendencias\n\n¿En qué te puedo ayudar hoy?',
      timestamp: new Date().toISOString(),
    },
  ]);
  const [input, setInput] = useState('');
  const [loading, setLoading] = useState(false);
  const flatListRef = useRef<FlatList>(null);

  const sendMessage = async () => {
    if (!input.trim() || loading) return;

    const userMsg: Message = {
      id: messages.length,
      role: 'user',
      content: input.trim(),
      timestamp: new Date().toISOString(),
    };

    setMessages(prev => [...prev, userMsg]);
    setInput('');
    setLoading(true);

    try {
      const response = await IAService.ask('general', input.trim());
      const assistantMsg: Message = {
        id: messages.length + 1,
        role: 'assistant',
        content: response.data?.data?.respuesta || 'Lo siento, no pude procesar tu consulta.',
        timestamp: new Date().toISOString(),
      };
      setMessages(prev => [...prev, assistantMsg]);
    } catch (error) {
      setMessages(prev => [...prev, {
        id: messages.length + 1, role: 'assistant',
        content: 'Error al conectar con el asistente IA. Verifica tu conexión.',
        timestamp: new Date().toISOString(),
      }]);
    } finally {
      setLoading(false);
      setTimeout(() => flatListRef.current?.scrollToEnd({ animated: true }), 100);
    }
  };

  const renderMessage = ({ item }: { item: Message }) => (
    <View style={[styles.messageBubble, item.role === 'user' ? styles.userBubble : styles.assistantBubble]}>
      {item.role === 'assistant' && (
        <Icon name="robot" size={18} color="#6f42c1" style={{ marginBottom: 4 }} />
      )}
      <Text style={[styles.messageText, item.role === 'user' ? styles.userText : styles.assistantText]}>
        {item.content}
      </Text>
    </View>
  );

  return (
    <KeyboardAvoidingView style={styles.container} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <FlatList
        ref={flatListRef}
        data={messages}
        renderItem={renderMessage}
        keyExtractor={item => item.id.toString()}
        style={styles.messageList}
        onContentSizeChange={() => flatListRef.current?.scrollToEnd({ animated: true })}
      />
      <View style={styles.inputContainer}>
        <TextInput
          style={styles.input}
          value={input}
          onChangeText={setInput}
          placeholder="Escribe tu consulta de planeación..."
          multiline
          maxLength={1000}
          editable={!loading}
        />
        <TouchableOpacity
          style={[styles.sendButton, (!input.trim() || loading) && styles.sendButtonDisabled]}
          onPress={sendMessage}
          disabled={!input.trim() || loading}
        >
          <Icon name="send" size={20} color="#fff" />
        </TouchableOpacity>
      </View>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f5f6fa' },
  messageList: { flex: 1, paddingHorizontal: 12 },
  messageBubble: {
    maxWidth: '80%', padding: 12, borderRadius: 16, marginVertical: 4,
  },
  userBubble: { backgroundColor: '#1a73e8', alignSelf: 'flex-end', borderBottomRightRadius: 4 },
  assistantBubble: { backgroundColor: '#fff', alignSelf: 'flex-start', borderBottomLeftRadius: 4, elevation: 1 },
  userText: { color: '#fff', fontSize: 15, lineHeight: 22 },
  assistantText: { color: '#333', fontSize: 15, lineHeight: 22 },
  inputContainer: {
    flexDirection: 'row', padding: 10, backgroundColor: '#fff',
    borderTopWidth: 1, borderTopColor: '#e0e0e0', alignItems: 'flex-end',
  },
  input: {
    flex: 1, backgroundColor: '#f0f0f0', borderRadius: 20,
    paddingHorizontal: 16, paddingVertical: 10, maxHeight: 100, fontSize: 15,
  },
  sendButton: {
    width: 44, height: 44, borderRadius: 22, backgroundColor: '#6f42c1',
    justifyContent: 'center', alignItems: 'center', marginLeft: 8,
  },
  sendButtonDisabled: { opacity: 0.4 },
});
