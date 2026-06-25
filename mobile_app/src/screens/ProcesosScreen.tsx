import React from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity } from 'react-native';
import { Card, Title, Paragraph, List } from 'react-native-paper';
import Icon from 'react-native-vector-icons/MaterialCommunityIcons';

export default function ProcesosScreen({ navigation }: any) {
  return (
    <ScrollView style={styles.container}>
      <Card style={styles.card}>
        <Card.Content>
          <Title>Mapa de Procesos</Title>
          <Paragraph>Macroprocesos, procesos, procedimientos y tareas</Paragraph>
        </Card.Content>
      </Card>

      <Card style={styles.card}>
        <Card.Content>
          <List.Item title="Procesos Estratégicos" description="Planeación, Direccionamiento" left={p => <Icon name="crown" size={24} color="#1a73e8" />} />
          <List.Item title="Procesos Misionales" description="Operación principal del negocio" left={p => <Icon name="cog" size={24} color="#28a745" />} />
          <List.Item title="Procesos de Apoyo" description="Soporte administrativo y logístico" left={p => <Icon name="handshake" size={24} color="#ffc107" />} />
          <List.Item title="Procesos de Evaluación" description="Auditoría, control y mejora continua" left={p => <Icon name="magnify" size={24} color="#6f42c1" />} />
        </Card.Content>
      </Card>

      <TouchableOpacity style={styles.docButton} onPress={() => navigation.navigate('Documentos')}>
        <Icon name="file-document" size={20} color="#1a73e8" />
        <Text style={styles.docButtonText}>Ver Documentos ISO de Procesos</Text>
      </TouchableOpacity>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f5f6fa' },
  card: { marginHorizontal: 12, marginTop: 12, elevation: 1 },
  docButton: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
    marginHorizontal: 16, marginTop: 20, padding: 14,
    backgroundColor: '#fff', borderRadius: 10, gap: 8, borderWidth: 1, borderColor: '#1a73e8'
  },
  docButtonText: { color: '#1a73e8', fontSize: 14, fontWeight: '500' },
});
