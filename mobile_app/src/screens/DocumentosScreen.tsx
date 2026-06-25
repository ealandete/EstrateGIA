import React from 'react';
import { View, ScrollView, StyleSheet, Text, TouchableOpacity } from 'react-native';
import { Card, Title, Paragraph, List, Chip, Searchbar } from 'react-native-paper';
import Icon from 'react-native-vector-icons/MaterialCommunityIcons';

export default function DocumentosScreen() {
  const normas = [
    { id: 1, codigo: 'ISO 9001:2015', nombre: 'Sistema de Gestión de Calidad', sector: 'General', docs: 12 },
    { id: 2, codigo: 'ISO 7101:2023', nombre: 'Gestión de Calidad en Salud', sector: 'Salud', docs: 8 },
    { id: 3, codigo: 'ISO 14001:2015', nombre: 'Gestión Ambiental', sector: 'General', docs: 5 },
    { id: 4, codigo: 'ISO 45001:2018', nombre: 'Seguridad y Salud en el Trabajo', sector: 'Salud', docs: 15 },
    { id: 5, codigo: 'ISO 13485:2016', nombre: 'Dispositivos Médicos - SGC', sector: 'Logística Farmacéutica', docs: 6 },
    { id: 6, codigo: 'ISO 41001:2018', nombre: 'Facility Management', sector: 'Inmobiliario', docs: 3 },
  ];

  const documentos = [
    { id: 1, titulo: 'Manual de Calidad Institucional', tipo: 'manual_calidad', norma: 'ISO 9001:2015', version: '3.2', estado: 'aprobado', fecha: '2026-04-15' },
    { id: 2, titulo: 'Procedimiento de Atención al Paciente', tipo: 'procedimiento', norma: 'ISO 7101:2023', version: '2.0', estado: 'publicado', fecha: '2026-03-20' },
    { id: 3, titulo: 'Instructivo de Cadena de Frío', tipo: 'instructivo', norma: 'GDP/BPD', version: '1.5', estado: 'revision', fecha: '2026-05-01' },
    { id: 4, titulo: 'Política de SST', tipo: 'politica', norma: 'ISO 45001:2018', version: '4.0', estado: 'aprobado', fecha: '2026-01-10' },
  ];

  const tipoLabels: { [key: string]: string } = {
    manual_calidad: 'Manual de Calidad', procedimiento: 'Procedimiento', instructivo: 'Instructivo',
    registro: 'Registro', formato: 'Formato', politica: 'Política', plan: 'Plan', informe: 'Informe',
    auditoria: 'Auditoría',
  };

  const estadoColors: { [key: string]: string } = {
    borrador: '#888', revision: '#ffc107', aprobado: '#28a745', publicado: '#1a73e8', obsoleto: '#dc3545',
  };

  return (
    <ScrollView style={styles.container}>
      <Searchbar placeholder="Buscar documentos..." style={styles.searchbar} onChangeText={() => {}} />

      <Text style={styles.sectionTitle}>Normas ISO Aplicables</Text>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.normasScroll}>
        {normas.map((n) => (
          <Card key={n.id} style={styles.normaCard}>
            <Card.Content>
              <Text style={styles.normaCodigo}>{n.codigo}</Text>
              <Text style={styles.normaNombre} numberOfLines={2}>{n.nombre}</Text>
              <Chip style={styles.sectorChip}>{n.sector}</Chip>
              <Text style={styles.docCount}>{n.docs} documentos</Text>
            </Card.Content>
          </Card>
        ))}
      </ScrollView>

      <Text style={styles.sectionTitle}>Documentos del Sistema</Text>
      {documentos.map((doc) => (
        <Card key={doc.id} style={styles.docCard}>
          <Card.Content>
            <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}>
              <Icon name="file-document-outline" size={24} color="#1a73e8" />
              <View style={{ flex: 1 }}>
                <Text style={styles.docTitle}>{doc.titulo}</Text>
                <View style={styles.docMeta}>
                  <Chip textStyle={{ fontSize: 10 }} style={{ height: 22 }}>{tipoLabels[doc.tipo] || doc.tipo}</Chip>
                  <Chip textStyle={{ fontSize: 10 }} style={{ height: 22 }}>{doc.norma}</Chip>
                  <Chip textStyle={{ fontSize: 10 }} style={{ height: 22 }}>v{doc.version}</Chip>
                </View>
              </View>
              <Chip
                style={{ backgroundColor: estadoColors[doc.estado] || '#888' }}
                textStyle={{ color: '#fff', fontSize: 10 }}
              >
                {doc.estado}
              </Chip>
            </View>
          </Card.Content>
        </Card>
      ))}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f5f6fa' },
  searchbar: { marginHorizontal: 12, marginTop: 10, elevation: 1 },
  sectionTitle: { fontSize: 16, fontWeight: '700', marginHorizontal: 16, marginTop: 16, marginBottom: 8 },
  normasScroll: { paddingHorizontal: 12, marginBottom: 8 },
  normaCard: { width: 160, marginRight: 10, elevation: 1 },
  normaCodigo: { fontSize: 13, fontWeight: '700', color: '#1a73e8' },
  normaNombre: { fontSize: 12, color: '#333', marginTop: 4, minHeight: 32 },
  sectorChip: { marginTop: 6, height: 24 },
  docCount: { fontSize: 11, color: '#888', marginTop: 6, textAlign: 'right' },
  docCard: { marginHorizontal: 12, marginBottom: 8, elevation: 1 },
  docTitle: { fontSize: 14, fontWeight: '500', marginBottom: 4 },
  docMeta: { flexDirection: 'row', gap: 4 },
});
