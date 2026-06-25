import React, { useState, useEffect } from 'react';
import { View, ScrollView, StyleSheet, Text, TouchableOpacity } from 'react-native';
import { Card, Title, Paragraph, ProgressBar, Chip, List, FAB, Badge } from 'react-native-paper';
import Icon from 'react-native-vector-icons/MaterialCommunityIcons';
import { PlanService } from '../services/ApiService';

export default function PlaneacionScreen({ navigation }: any) {
  const [planes, setPlanes] = useState<any[]>([]);
  const [selectedPlan, setSelectedPlan] = useState<any>(null);
  const [arbol, setArbol] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadPlanes();
  }, []);

  const loadPlanes = async () => {
    try {
      const response = await PlanService.getPlanes();
      if (response.data.success) {
        const allPlanes = response.data.data || [];
        setPlanes(allPlanes);
        if (allPlanes.length > 0) {
          setSelectedPlan(allPlanes[0]);
          loadArbol(allPlanes[0].plan_id);
        }
      }
    } finally {
      setLoading(false);
    }
  };

  const loadArbol = async (planId: number) => {
    try {
      const response = await PlanService.getArbol(planId);
      if (response.data.success) {
        setArbol(response.data.data || []);
      }
    } catch (e) {}
  };

  const handleSelectPlan = (plan: any) => {
    setSelectedPlan(plan);
    loadArbol(plan.plan_id);
  };

  const estadoColors: { [key: string]: string } = {
    borrador: '#888', en_proceso: '#1a73e8', revision: '#ffc107',
    aprobado: '#28a745', ejecucion: '#6f42c1', completado: '#28a745', cancelado: '#dc3545',
  };

  return (
    <View style={styles.container}>
      {/* Selector de planes */}
      <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.planSelector}>
        {planes.map((plan) => (
          <Chip
            key={plan.plan_id}
            selected={selectedPlan?.plan_id === plan.plan_id}
            onPress={() => handleSelectPlan(plan)}
            style={[styles.planChip, selectedPlan?.plan_id === plan.plan_id && styles.planChipSelected]}
          >
            {plan.plan_nombre?.substring(0, 20)}
          </Chip>
        ))}
      </ScrollView>

      {selectedPlan && (
        <ScrollView style={styles.content}>
          {/* Info del plan */}
          <Card style={styles.card}>
            <Card.Content>
              <View style={{ flexDirection: 'row', alignItems: 'center', gap: 12 }}>
                <Icon name="bullseye-arrow" size={32} color="#1a73e8" />
                <View style={{ flex: 1 }}>
                  <Title>{selectedPlan.plan_nombre}</Title>
                  <Paragraph>{selectedPlan.metodologia_nombre} - {selectedPlan.plan_periodo}</Paragraph>
                </View>
                <Badge style={{ backgroundColor: estadoColors[selectedPlan.plan_estado] || '#888' }}>
                  {selectedPlan.plan_estado}
                </Badge>
              </View>
              <View style={{ marginTop: 12 }}>
                <Text style={styles.progressLabel}>Avance: {selectedPlan.plan_avance_porcentaje}%</Text>
                <ProgressBar progress={selectedPlan.plan_avance_porcentaje / 100} color="#1a73e8" style={{ height: 8, borderRadius: 4 }} />
              </View>
            </Card.Content>
          </Card>

          {/* Árbol de planeación: Fases → Objetivos → Estrategias → Actividades */}
          <Text style={styles.sectionTitle}>Estructura del Plan</Text>
          {arbol.map((fase: any) => (
            <Card key={fase.fase_id} style={styles.faseCard}>
              <Card.Content>
                <List.Accordion
                  title={fase.fase_nombre}
                  description={`${fase.fase_avance_porcentaje || 0}% | ${fase.fase_estado || 'pendiente'}`}
                  left={props => <Icon name="flag-checkered" size={24} color="#1a73e8" />}
                >
                  {fase.objetivos?.map((obj: any) => (
                    <List.Accordion
                      key={obj.objetivo_id}
                      title={obj.objetivo_nombre}
                      description={`${obj.objetivo_perspectiva} - ${obj.objetivo_avance_porcentaje || 0}%`}
                      style={{ paddingLeft: 20 }}
                      left={props => <Icon name="target" size={20} color="#6f42c1" />}
                    >
                      {obj.estrategias?.map((est: any) => (
                        <List.Accordion
                          key={est.estrategia_id}
                          title={est.estrategia_nombre}
                          description={`${est.estrategia_tipo} - ${est.estrategia_avance_porcentaje || 0}%`}
                          style={{ paddingLeft: 40 }}
                          left={props => <Icon name="lightbulb-outline" size={18} color="#ff9800" />}
                        >
                          {est.actividades?.map((act: any) => (
                            <List.Item
                              key={act.actividad_id}
                              title={act.actividad_nombre}
                              description={`${act.actividad_estado} | ${act.actividad_avance_porcentaje || 0}%`}
                              style={{ paddingLeft: 60 }}
                              left={props => <Icon name="checkbox-blank-circle" size={12} color={act.actividad_estado === 'completada' ? '#28a745' : '#888'} />}
                              right={props => act.actividad_prioridad === 'critico' ? <Badge size={20}>!</Badge> : null}
                            />
                          ))}
                        </List.Accordion>
                      ))}
                    </List.Accordion>
                  ))}
                </List.Accordion>
              </Card.Content>
              <ProgressBar progress={(fase.fase_avance_porcentaje || 0) / 100} color="#1a73e8" style={{ height: 3 }} />
            </Card>
          ))}
        </ScrollView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f5f6fa' },
  planSelector: { maxHeight: 50, paddingHorizontal: 8, paddingVertical: 8, backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: '#e0e0e0' },
  planChip: { marginHorizontal: 4 },
  planChipSelected: { backgroundColor: '#e8f0fe' },
  content: { flex: 1 },
  card: { marginHorizontal: 12, marginTop: 12 },
  progressLabel: { fontSize: 13, color: '#666', marginBottom: 4 },
  sectionTitle: { fontSize: 18, fontWeight: '700', marginHorizontal: 16, marginTop: 16, marginBottom: 8 },
  faseCard: { marginHorizontal: 12, marginBottom: 8, elevation: 1 },
});
