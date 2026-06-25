import React, { useState, useEffect } from 'react';
import { View, ScrollView, StyleSheet, Text } from 'react-native';
import { Card, Title, ProgressBar, Badge, SegmentedButtons } from 'react-native-paper';
import { LineChart } from 'react-native-chart-kit';
import { Dimensions } from 'react-native';
import Icon from 'react-native-vector-icons/MaterialCommunityIcons';
import { IndicadorService } from '../services/ApiService';

const screenWidth = Dimensions.get('window').width;

const variantInfo = {
  cumplimiento: { color: '#28a745', icon: 'check-circle', label: 'Cumplimiento' },
  oportunidad: { color: '#ffc107', icon: 'clock-outline', label: 'Oportunidad' },
  calidad: { color: '#007bff', icon: 'star', label: 'Calidad' },
  productividad: { color: '#6f42c1', icon: 'chart-line', label: 'Productividad' },
};

export default function IndicadoresScreen() {
  const [variantes, setVariantes] = useState<any>({});
  const [semaforo, setSemaforo] = useState<any[]>([]);
  const [tendencia, setTendencia] = useState<any>({});
  const [tab, setTab] = useState('semaforo');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      const planId = 1;
      const [varRes, semRes, tenRes] = await Promise.all([
        IndicadorService.getVariantes(planId),
        IndicadorService.getSemaforo(planId),
        IndicadorService.getTendencia(planId),
      ]);
      if (varRes.data.success) setVariantes(varRes.data.data);
      if (semRes.data.success) setSemaforo(semRes.data.data || []);
      if (tenRes.data.success) setTendencia(tenRes.data.data || {});
    } finally {
      setLoading(false);
    }
  };

  const renderSemaforoLegend = () => (
    <View style={styles.legendRow}>
      <Badge size={20} style={{ backgroundColor: '#28a745' }} />
      <Text style={styles.legendText}>Verde {'≥'} 90%</Text>
      <Badge size={20} style={{ backgroundColor: '#ffc107' }} />
      <Text style={styles.legendText}>Amarillo {'≥'} 70%</Text>
      <Badge size={20} style={{ backgroundColor: '#dc3545' }} />
      <Text style={styles.legendText}>Rojo {'<'} 70%</Text>
    </View>
  );

  return (
    <ScrollView style={styles.container}>
      <SegmentedButtons
        value={tab}
        onValueChange={setTab}
        buttons={[
          { value: 'semaforo', label: 'Semáforo' },
          { value: 'tendencia', label: 'Tendencia' },
          { value: 'detalle', label: 'Por Tipo' },
        ]}
        style={styles.segmented}
      />

      {renderSemaforoLegend()}

      {tab === 'semaforo' && (
        <View style={styles.grid}>
          {semaforo.map((sem: any, idx: number) => {
            const info = variantInfo[sem.categoria_tipo as keyof typeof variantInfo];
            if (!info) return null;
            return (
              <Card key={idx} style={[styles.semaforoCard, { borderLeftColor: info.color }]}>
                <Card.Content>
                  <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}>
                    <Icon name={info.icon} size={24} color={info.color} />
                    <Title style={{ fontSize: 15 }}>{sem.categoria_nombre}</Title>
                  </View>
                  <View style={styles.semaforoValues}>
                    <View style={styles.semaforoBox}>
                      <View style={[styles.semaforoCircle, { backgroundColor: '#28a745' }]} />
                      <Text style={styles.semaforoNum}>{sem.verde || 0}</Text>
                    </View>
                    <View style={styles.semaforoBox}>
                      <View style={[styles.semaforoCircle, { backgroundColor: '#ffc107' }]} />
                      <Text style={styles.semaforoNum}>{sem.amarillo || 0}</Text>
                    </View>
                    <View style={styles.semaforoBox}>
                      <View style={[styles.semaforoCircle, { backgroundColor: '#dc3545' }]} />
                      <Text style={styles.semaforoNum}>{sem.rojo || 0}</Text>
                    </View>
                  </View>
                  <ProgressBar
                    progress={(sem.verde || 0) / (sem.total || 1)}
                    color={info.color}
                    style={{ height: 6, borderRadius: 3, marginTop: 8 }}
                  />
                </Card.Content>
              </Card>
            );
          })}
        </View>
      )}

      {tab === 'tendencia' && (
        <View>
          {Object.entries(tendencia).map(([tipo, data]: [string, any]) => {
            const info = variantInfo[tipo as keyof typeof variantInfo];
            if (!info || !data?.length) return null;
            const chartData = {
              labels: data.slice(-6).map((d: any) => d.medicion_periodo?.substring(5) || ''),
              datasets: [{ data: data.slice(-6).map((d: any) => d.cumplimiento_promedio || d.valor_promedio || 0) }],
            };
            return (
              <Card key={tipo} style={styles.chartCard}>
                <Card.Content>
                  <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}>
                    <Icon name={info.icon} size={20} color={info.color} />
                    <Title style={{ fontSize: 14 }}>{info.label}</Title>
                  </View>
                  <LineChart
                    data={chartData}
                    width={screenWidth - 56}
                    height={160}
                    chartConfig={{
                      backgroundColor: '#fff',
                      backgroundGradientFrom: '#fff',
                      backgroundGradientTo: '#fff',
                      decimalPlaces: 1,
                      color: (opacity = 1) => info.color,
                      labelColor: () => '#666',
                      propsForDots: { r: '4', strokeWidth: '2', stroke: info.color },
                    }}
                    bezier
                    style={styles.chart}
                  />
                </Card.Content>
              </Card>
            );
          })}
        </View>
      )}

      {tab === 'detalle' && (
        <View style={styles.grid}>
          {Object.entries(variantes).map(([tipo, data]: [string, any]) => {
            const info = variantInfo[tipo as keyof typeof variantInfo];
            if (!info) return null;
            return (
              <Card key={tipo} style={[styles.detailCard]}>
                <Card.Content>
                  <Icon name={info.icon} size={32} color={info.color} />
                  <Title style={{ fontSize: 14, marginTop: 8 }}>{data?.categoria_nombre}</Title>
                  <Text style={[styles.bigValue, { color: info.color }]}>
                    {data?.promedio_cumplimiento?.toFixed(1) || '0'}%
                  </Text>
                  <Text style={styles.metaText}>
                    {data?.total_indicadores || 0} indicadores | {data?.total_mediciones || 0} mediciones
                  </Text>
                </Card.Content>
              </Card>
            );
          })}
        </View>
      )}

      <View style={{ height: 40 }} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f5f6fa' },
  segmented: { marginHorizontal: 12, marginVertical: 10 },
  legendRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6, marginBottom: 10 },
  legendText: { fontSize: 11, color: '#666', marginRight: 12 },
  grid: { paddingHorizontal: 8 },
  semaforoCard: { marginHorizontal: 4, marginBottom: 10, borderLeftWidth: 4, elevation: 1 },
  semaforoValues: { flexDirection: 'row', justifyContent: 'space-around', marginTop: 10 },
  semaforoBox: { alignItems: 'center' },
  semaforoCircle: { width: 24, height: 24, borderRadius: 12, marginBottom: 4 },
  semaforoNum: { fontSize: 18, fontWeight: '700' },
  detailCard: { marginBottom: 10, alignItems: 'center', paddingVertical: 10, elevation: 1 },
  bigValue: { fontSize: 36, fontWeight: '700', marginTop: 4 },
  metaText: { fontSize: 11, color: '#888', marginTop: 4 },
  chartCard: { marginHorizontal: 12, marginBottom: 12, elevation: 1 },
  chart: { marginTop: 8, borderRadius: 8 },
});
