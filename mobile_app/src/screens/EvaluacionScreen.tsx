import React, { useState } from 'react';
import { View, ScrollView, StyleSheet, Text } from 'react-native';
import { Card, Title, Paragraph, ProgressBar } from 'react-native-paper';
import Icon from 'react-native-vector-icons/MaterialCommunityIcons';

export default function EvaluacionScreen() {
  const evaluacion = {
    periodo: '2026-05',
    cumplimiento: 87.5,
    oportunidad: 72.3,
    calidad: 91.0,
    productividad: 78.5,
    total: 82.1,
  };

  return (
    <ScrollView style={styles.container}>
      <Card style={styles.card}>
        <Card.Content style={{ alignItems: 'center' }}>
          <Title>Mi Evaluación de Desempeño</Title>
          <Paragraph>Período: {evaluacion.periodo}</Paragraph>
          <View style={styles.scoreCircle}>
            <Text style={styles.scoreText}>{evaluacion.total.toFixed(1)}%</Text>
          </View>
        </Card.Content>
      </Card>

      <Card style={styles.card}>
        <Card.Content>
          <Title style={{ marginBottom: 16 }}>Desglose por Variante</Title>
          {[
            { label: 'Cumplimiento', value: evaluacion.cumplimiento, color: '#28a745', icon: 'check-circle', weight: '30%' },
            { label: 'Oportunidad', value: evaluacion.oportunidad, color: '#ffc107', icon: 'clock-outline', weight: '25%' },
            { label: 'Calidad', value: evaluacion.calidad, color: '#007bff', icon: 'star', weight: '20%' },
            { label: 'Productividad', value: evaluacion.productividad, color: '#6f42c1', icon: 'chart-line', weight: '25%' },
          ].map((item, idx) => (
            <View key={idx} style={styles.variantRow}>
              <Icon name={item.icon} size={24} color={item.color} />
              <View style={styles.variantInfo}>
                <Text style={styles.variantLabel}>{item.label} <Text style={{ fontSize: 11, color: '#888' }}>({item.weight})</Text></Text>
                <ProgressBar progress={item.value / 100} color={item.color} style={{ height: 8, borderRadius: 4 }} />
              </View>
              <Text style={[styles.variantValue, { color: item.color }]}>{item.value.toFixed(1)}%</Text>
            </View>
          ))}
        </Card.Content>
      </Card>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f5f6fa' },
  card: { marginHorizontal: 12, marginTop: 12, elevation: 1 },
  scoreCircle: {
    width: 100, height: 100, borderRadius: 50, backgroundColor: '#1a73e8',
    justifyContent: 'center', alignItems: 'center', marginTop: 12
  },
  scoreText: { color: '#fff', fontSize: 28, fontWeight: '700' },
  variantRow: { flexDirection: 'row', alignItems: 'center', marginBottom: 16, gap: 12 },
  variantInfo: { flex: 1 },
  variantLabel: { fontSize: 14, fontWeight: '500', marginBottom: 4 },
  variantValue: { fontSize: 20, fontWeight: '700', width: 60, textAlign: 'right' },
});
