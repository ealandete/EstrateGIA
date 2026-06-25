import React, { useState, useCallback } from 'react';
import { View, ScrollView, StyleSheet, Text, TouchableOpacity } from 'react-native';
import { Card, Title, Paragraph, Chip, List, FAB, ProgressBar, Badge } from 'react-native-paper';
import Icon from 'react-native-vector-icons/MaterialCommunityIcons';

export default function ActividadesScreen({ navigation }: any) {
  const [tab, setTab] = useState<'pendientes' | 'completadas'>('pendientes');

  const actividades = [
    { id: 1, nombre: 'Análisis FODA del departamento', estado: 'en_progreso', avance: 60, prioridad: 'alto', fecha: '2026-05-20', responsable: 'Carlos López', tiempo_estimado: 480 },
    { id: 2, nombre: 'Definir KPIs de productividad', estado: 'pendiente', avance: 0, prioridad: 'critico', fecha: '2026-05-15', responsable: 'Ana Martínez', tiempo_estimado: 240 },
    { id: 3, nombre: 'Revisión del plan anual de compras', estado: 'completada', avance: 100, prioridad: 'medio', fecha: '2026-05-10', responsable: 'María García', tiempo_estimado: 120 },
    { id: 4, nombre: 'Mapeo de procesos de farmacia', estado: 'en_progreso', avance: 35, prioridad: 'alto', fecha: '2026-05-25', responsable: 'Juan Pérez', tiempo_estimado: 960 },
  ];

  const filtered = actividades.filter(a => tab === 'pendientes' ? a.estado !== 'completada' : a.estado === 'completada');

  const getPrioridadColor = (p: string) => {
    switch (p) { case 'critico': return '#dc3545'; case 'alto': return '#ffc107'; case 'medio': return '#1a73e8'; default: return '#888'; }
  };

  return (
    <View style={styles.container}>
      <View style={styles.tabRow}>
        <Chip selected={tab === 'pendientes'} onPress={() => setTab('pendientes')} style={styles.tab}>Pendientes</Chip>
        <Chip selected={tab === 'completadas'} onPress={() => setTab('completadas')} style={styles.tab}>Completadas</Chip>
      </View>
      <ScrollView style={styles.list}>
        {filtered.map((act: any) => (
          <Card key={act.id} style={styles.card}>
            <Card.Content>
              <View style={styles.actHeader}>
                <Badge style={{ backgroundColor: getPrioridadColor(act.prioridad) }} size={8} />
                <Title style={styles.actTitle}>{act.nombre}</Title>
              </View>
              <View style={styles.actMeta}>
                <Chip icon="account" style={styles.metaChip}>{act.responsable}</Chip>
                <Chip icon="calendar" style={styles.metaChip}>{act.fecha}</Chip>
                <Chip icon="clock-outline" style={styles.metaChip}>{Math.round(act.tiempo_estimado / 60)}h</Chip>
              </View>
              <ProgressBar progress={act.avance / 100} color={act.estado === 'completada' ? '#28a745' : '#1a73e8'} style={{ height: 6, borderRadius: 3, marginTop: 8 }} />
              <Text style={styles.avanceText}>{act.avance}%</Text>
              {act.estado === 'en_progreso' && (
                <TouchableOpacity style={styles.timerButton}>
                  <Icon name="timer-outline" size={16} color="#fff" />
                  <Text style={styles.timerText}>Registrar Tiempo</Text>
                </TouchableOpacity>
              )}
            </Card.Content>
          </Card>
        ))}
      </ScrollView>
      <FAB icon="plus" style={styles.fab} onPress={() => {}} label="Nueva Actividad" />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f5f6fa' },
  tabRow: { flexDirection: 'row', padding: 8, gap: 8, backgroundColor: '#fff' },
  tab: { flex: 1, justifyContent: 'center' },
  list: { flex: 1 },
  card: { marginHorizontal: 10, marginTop: 8, elevation: 1 },
  actHeader: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  actTitle: { fontSize: 15, flex: 1 },
  actMeta: { flexDirection: 'row', flexWrap: 'wrap', gap: 6, marginTop: 8 },
  metaChip: { height: 28 },
  avanceText: { fontSize: 11, color: '#888', textAlign: 'right', marginTop: 4 },
  timerButton: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6,
    backgroundColor: '#6f42c1', marginTop: 10, padding: 8, borderRadius: 8
  },
  timerText: { color: '#fff', fontSize: 13, fontWeight: '500' },
  fab: { position: 'absolute', margin: 16, right: 0, bottom: 0, backgroundColor: '#1a73e8' },
});
