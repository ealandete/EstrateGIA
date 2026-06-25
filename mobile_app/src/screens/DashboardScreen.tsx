import React, { useState, useEffect, useCallback } from 'react';
import {
  View, ScrollView, StyleSheet, Text, TouchableOpacity, RefreshControl, Dimensions
} from 'react-native';
import { Card, Title, Paragraph, ProgressBar, Badge, Chip, Avatar, useTheme } from 'react-native-paper';
import { LineChart, PieChart } from 'react-native-chart-kit';
import Icon from 'react-native-vector-icons/MaterialCommunityIcons';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { DashboardService } from '../services/ApiService';

const screenWidth = Dimensions.get('window').width;

const variantColors = {
  cumplimiento: '#28a745',
  oportunidad: '#ffc107',
  calidad: '#007bff',
  productividad: '#6f42c1',
};

const variantIcons: { [key: string]: string } = {
  cumplimiento: 'check-circle',
  oportunidad: 'clock-outline',
  calidad: 'star',
  productividad: 'chart-line',
};

export default function DashboardScreen({ navigation }: any) {
  const [dashboard, setDashboard] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const loadDashboard = useCallback(async () => {
    try {
      const userStr = await AsyncStorage.getItem('user');
      const user = userStr ? JSON.parse(userStr) : null;
      const empresaId = 1; // Default, should come from user context

      const response = await DashboardService.ejecutivo(empresaId);
      if (response.data.success) {
        setDashboard(response.data.data);
      }
    } catch (error) {
      console.error('Error loading dashboard:', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => {
    loadDashboard();
  }, [loadDashboard]);

  const onRefresh = () => {
    setRefreshing(true);
    loadDashboard();
  };

  if (loading || !dashboard) {
    return (
      <View style={styles.loadingContainer}>
        <Text>Cargando dashboard...</Text>
      </View>
    );
  }

  const resumen = dashboard.resumen_planeacion;
  const plan = resumen?.plan_activo;
  const variantes = resumen?.variantes_kpi || {};
  const semaforo = dashboard.semaforo_kpis || [];
  const alertas = dashboard.alertas || [];
  const ranking = dashboard.ranking_colaboradores || [];

  return (
    <ScrollView
      style={styles.container}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
    >
      {/* Plan Activo */}
      {plan && (
        <Card style={styles.card}>
          <Card.Content>
            <View style={styles.planHeader}>
              <Icon name="bullseye-arrow" size={24} color="#1a73e8" />
              <View style={{ flex: 1, marginLeft: 12 }}>
                <Title style={styles.planTitle}>{plan.nombre}</Title>
                <Paragraph>{plan.metodologia} - {plan.estado}</Paragraph>
              </View>
              <Chip mode="outlined" style={{ backgroundColor: '#e8f0fe' }}>
                {plan.periodo}
              </Chip>
            </View>
            <View style={styles.progressContainer}>
              <Text style={styles.progressLabel}>Avance General: {plan.avance}%</Text>
              <ProgressBar progress={plan.avance / 100} color="#1a73e8" style={styles.progressBar} />
            </View>
            {plan.dias_restantes !== null && (
              <Text style={styles.daysRemaining}>
                {plan.dias_restantes} días restantes
              </Text>
            )}
          </Card.Content>
        </Card>
      )}

      {/* 4 Variantes KPI */}
      <Text style={styles.sectionTitle}>Las 4 Variantes</Text>
      <View style={styles.variantsGrid}>
        {Object.entries(variants).map(([tipo, data]: [string, any]) => (
          <TouchableOpacity
            key={tipo}
            style={[styles.variantCard, { borderLeftColor: variantColors[tipo as keyof typeof variantColors] }]}
            onPress={() => navigation.navigate('Indicadores')}
          >
            <Icon name={variantIcons[tipo] || 'circle'} size={28} color={variantColors[tipo as keyof typeof variantColors]} />
            <Text style={styles.variantTitle}>{data?.categoria_nombre || tipo}</Text>
            <Text style={[styles.variantValue, { color: variantColors[tipo as keyof typeof variantColors] }]}>
              {data?.promedio_cumplimiento?.toFixed(1) || '0'}%
            </Text>
            <View style={styles.semaforoMini}>
              <Badge size={16} style={{ backgroundColor: '#28a745' }}>{data?.conteo_verde || 0}</Badge>
              <Badge size={16} style={{ backgroundColor: '#ffc107' }}>{data?.conteo_amarillo || 0}</Badge>
              <Badge size={16} style={{ backgroundColor: '#dc3545' }}>{data?.conteo_rojo || 0}</Badge>
            </View>
          </TouchableOpacity>
        ))}
      </View>

      {/* Semáforo por categoría */}
      <Text style={styles.sectionTitle}>Semáforo de Indicadores</Text>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.semaforoScroll}>
        {semaforo.map((sem: any, idx: number) => (
          <Card key={idx} style={styles.semaforoCard}>
            <Card.Content>
              <Text style={styles.semaforoTitle}>{sem.categoria_nombre}</Text>
              <View style={styles.semaforoRow}>
                <View style={styles.semaforoItem}>
                  <View style={[styles.semaforoDot, { backgroundColor: '#28a745' }]} />
                  <Text style={styles.semaforoValue}>{sem.verde}</Text>
                </View>
                <View style={styles.semaforoItem}>
                  <View style={[styles.semaforoDot, { backgroundColor: '#ffc107' }]} />
                  <Text style={styles.semaforoValue}>{sem.amarillo}</Text>
                </View>
                <View style={styles.semaforoItem}>
                  <View style={[styles.semaforoDot, { backgroundColor: '#dc3545' }]} />
                  <Text style={styles.semaforoValue}>{sem.rojo}</Text>
                </View>
              </View>
              <ProgressBar
                progress={(sem.verde || 0) / (sem.total || 1)}
                color={sem.categoria_color}
                style={styles.miniProgress}
              />
            </Card.Content>
          </Card>
        ))}
      </ScrollView>

      {/* Ranking Colaboradores */}
      <Text style={styles.sectionTitle}>Top Colaboradores</Text>
      <Card style={styles.card}>
        <Card.Content>
          {ranking.slice(0, 5).map((col: any, idx: number) => (
            <View key={idx} style={styles.rankingRow}>
              <Text style={styles.rankingPos}>#{idx + 1}</Text>
              <Avatar.Text size={32} label={col.nombre?.charAt(0) || 'U'} />
              <View style={styles.rankingInfo}>
                <Text style={styles.rankingName}>{col.nombre}</Text>
                <Text style={styles.rankingDept}>{col.usuario_departamento}</Text>
              </View>
              <Chip mode="outlined" style={styles.rankingScore}>
                {col.evaluacion_puntaje_total?.toFixed(1)}%
              </Chip>
            </View>
          ))}
        </Card.Content>
      </Card>

      {/* Alertas */}
      {alertas.length > 0 && (
        <>
          <Text style={styles.sectionTitle}>Alertas ({alertas.length})</Text>
          {alertas.slice(0, 5).map((alerta: any, idx: number) => (
            <Card key={idx} style={[styles.alertCard, {
              borderLeftColor: alerta.prioridad === 'alta' ? '#dc3545' : '#ffc107'
            }]}>
              <Card.Content>
                <View style={styles.alertRow}>
                  <Icon
                    name={alerta.prioridad === 'alta' ? 'alert-circle' : 'alert'}
                    size={20}
                    color={alerta.prioridad === 'alta' ? '#dc3545' : '#ffc107'}
                  />
                  <Text style={styles.alertText} numberOfLines={2}>{alerta.mensaje}</Text>
                </View>
                {alerta.responsable && (
                  <Text style={styles.alertMeta}>Responsable: {alerta.responsable}</Text>
                )}
              </Card.Content>
            </Card>
          ))}
        </>
      )}

      {/* Acceso rápido a IA */}
      <TouchableOpacity
        style={styles.iaButton}
        onPress={() => navigation.navigate('IAAsistente')}
      >
        <Icon name="robot" size={24} color="#fff" />
        <Text style={styles.iaButtonText}>Consultar al Asistente IA</Text>
      </TouchableOpacity>

      <View style={{ height: 40 }} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f5f6fa' },
  loadingContainer: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  card: { marginHorizontal: 12, marginTop: 12, elevation: 2 },
  planHeader: { flexDirection: 'row', alignItems: 'center' },
  planTitle: { fontSize: 16, fontWeight: '700' },
  progressContainer: { marginTop: 12 },
  progressLabel: { fontSize: 13, color: '#666', marginBottom: 4 },
  progressBar: { height: 10, borderRadius: 5 },
  daysRemaining: { fontSize: 12, color: '#999', marginTop: 8, textAlign: 'right' },
  sectionTitle: {
    fontSize: 18, fontWeight: '700', color: '#333',
    marginHorizontal: 16, marginTop: 20, marginBottom: 8
  },
  variantsGrid: { flexDirection: 'row', flexWrap: 'wrap', paddingHorizontal: 8 },
  variantCard: {
    width: '46%', backgroundColor: '#fff', margin: 8, padding: 14,
    borderRadius: 12, borderLeftWidth: 4, elevation: 1
  },
  variantTitle: { fontSize: 13, color: '#666', marginTop: 6 },
  variantValue: { fontSize: 28, fontWeight: '700', marginTop: 4 },
  semaforoMini: { flexDirection: 'row', gap: 6, marginTop: 8 },
  semaforoScroll: { paddingHorizontal: 12, marginTop: 4 },
  semaforoCard: { width: 160, marginRight: 10, elevation: 1 },
  semaforoTitle: { fontSize: 13, fontWeight: '600', marginBottom: 8 },
  semaforoRow: { flexDirection: 'row', justifyContent: 'space-around' },
  semaforoItem: { alignItems: 'center' },
  semaforoDot: { width: 12, height: 12, borderRadius: 6, marginBottom: 4 },
  semaforoValue: { fontSize: 11, color: '#666' },
  miniProgress: { height: 4, borderRadius: 2, marginTop: 8 },
  rankingRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 8, borderBottomWidth: 0.5, borderBottomColor: '#eee' },
  rankingPos: { fontSize: 16, fontWeight: '700', width: 36, color: '#1a73e8' },
  rankingInfo: { flex: 1, marginLeft: 10 },
  rankingName: { fontSize: 14, fontWeight: '500' },
  rankingDept: { fontSize: 12, color: '#888' },
  rankingScore: { backgroundColor: '#e8f5e9' },
  alertCard: { marginHorizontal: 12, marginTop: 6, borderLeftWidth: 4, elevation: 1 },
  alertRow: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  alertText: { flex: 1, fontSize: 13, color: '#333' },
  alertMeta: { fontSize: 11, color: '#888', marginTop: 4, marginLeft: 28 },
  iaButton: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
    backgroundColor: '#6f42c1', marginHorizontal: 16, marginTop: 20,
    padding: 16, borderRadius: 12, gap: 10, elevation: 3
  },
  iaButtonText: { color: '#fff', fontSize: 16, fontWeight: '600' },
});
