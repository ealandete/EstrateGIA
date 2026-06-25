import AsyncStorage from '@react-native-async-storage/async-storage';

const BASE_URL = 'http://localhost/api';
const MAX_RETRIES = 3;
const RETRY_DELAY = 1000;

interface ApiResponse<T = any> {
  success: boolean;
  data: T;
  message: string;
  timestamp: string;
}

interface ApiError {
  success: false;
  error: {
    code: string;
    message: string;
    details?: any;
  };
}

class ApiServiceClass {
  private token: string | null = null;

  setToken(token: string) {
    this.token = token;
  }

  clearToken() {
    this.token = null;
  }

  private getHeaders(): Record<string, string> {
    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    if (this.token) {
      headers['Authorization'] = `Bearer ${this.token}`;
    }
    return headers;
  }

  private async request<T>(
    method: string,
    endpoint: string,
    data?: any,
    retryCount = 0
  ): Promise<{ data: ApiResponse<T>; status: number }> {
    try {
      const config: RequestInit = {
        method,
        headers: this.getHeaders(),
      };

      if (data && method !== 'GET') {
        config.body = JSON.stringify(data);
      }

      const url = `${BASE_URL}${endpoint}`;
      const response = await fetch(url, config);
      const responseData = await response.json();

      if (response.status === 401) {
        await AsyncStorage.removeItem('auth_token');
        this.clearToken();
      }

      if (!response.ok && retryCount < MAX_RETRIES) {
        await new Promise(resolve => setTimeout(resolve, RETRY_DELAY * (retryCount + 1)));
        return this.request<T>(method, endpoint, data, retryCount + 1);
      }

      return { data: responseData, status: response.status };
    } catch (error: any) {
      if (retryCount < MAX_RETRIES) {
        await new Promise(resolve => setTimeout(resolve, RETRY_DELAY * (retryCount + 1)));
        return this.request<T>(method, endpoint, data, retryCount + 1);
      }
      throw error;
    }
  }

  get<T = any>(endpoint: string) {
    return this.request<T>('GET', endpoint);
  }

  post<T = any>(endpoint: string, data?: any) {
    return this.request<T>('POST', endpoint, data);
  }

  put<T = any>(endpoint: string, data?: any) {
    return this.request<T>('PUT', endpoint, data);
  }

  delete<T = any>(endpoint: string) {
    return this.request<T>('DELETE', endpoint);
  }
}

// ---- Tipos ----

export interface Usuario {
  usuario_id: number;
  usuario_nombre: string;
  usuario_apellido: string;
  usuario_email: string;
  usuario_cargo: string;
  usuario_departamento: string;
  usuario_foto_url: string;
  usuario_rol_id: number;
}

export interface PlanEstrategico {
  plan_id: number;
  plan_nombre: string;
  plan_estado: string;
  plan_avance_porcentaje: number;
  metodologia_nombre: string;
  metodologia_icono: string;
  plan_periodo: string;
  empresa_nombre: string;
}

export interface Actividad {
  actividad_id: number;
  actividad_nombre: string;
  actividad_descripcion: string;
  actividad_estado: string;
  actividad_avance_porcentaje: number;
  actividad_prioridad: string;
  actividad_fecha_fin_planeada: string;
  responsable_nombre: string;
}

export interface Indicador {
  indicador_id: number;
  indicador_nombre: string;
  categoria_nombre: string;
  categoria_tipo: string;
  categoria_color: string;
}

export interface Medicion {
  medicion_id: number;
  medicion_valor: number;
  medicion_fecha: string;
  medicion_cumplimiento_porcentaje: number;
  medicion_semaforo: string;
}

export interface EvaluacionDesempeno {
  evaluacion_id: number;
  evaluacion_periodo: string;
  evaluacion_puntaje_cumplimiento: number;
  evaluacion_puntaje_oportunidad: number;
  evaluacion_puntaje_calidad: number;
  evaluacion_puntaje_productividad: number;
  evaluacion_puntaje_total: number;
}

// ---- Servicios tipados ----

export const AuthService = {
  login: (email: string, password: string) =>
    ApiService.post('/auth/login', { email, password }),
  me: () => ApiService.get<Usuario>('/auth/me'),
};

export const DashboardService = {
  ejecutivo: (empresaId: number) =>
    ApiService.get(`/dashboard/ejecutivo?empresa_id=${empresaId}`),
  colaborador: () =>
    ApiService.get('/dashboard/colaborador'),
};

export const PlanService = {
  getPlanes: (empresaId?: number) =>
    ApiService.get<PlanEstrategico[]>(`/planes${empresaId ? `?empresa_id=${empresaId}` : ''}`),
  getPlan: (id: number) =>
    ApiService.get(`/planes/${id}`),
  getArbol: (id: number) =>
    ApiService.get(`/planes/${id}/arbol`),
  createPlan: (data: any) =>
    ApiService.post('/planes', data),
};

export const ActividadService = {
  getMisActividades: () =>
    ApiService.get<Actividad[]>('/actividades/mis-asignadas'),
  updateEstado: (mapaId: number, estado: string) =>
    ApiService.put(`/mapa-actividades/${mapaId}/estado`, { estado }),
  iniciarTiempo: (tareaId: number) =>
    ApiService.post('/tiempos/iniciar', { tarea_id: tareaId }),
  finalizarTiempo: (mapeoId: number) =>
    ApiService.put(`/tiempos/${mapeoId}/finalizar`),
};

export const IndicadorService = {
  getVariantes: (planId: number) =>
    ApiService.get(`/variantes/resumen?plan_id=${planId}`),
  getSemaforo: (planId: number) =>
    ApiService.get(`/variantes/semaforo?plan_id=${planId}`),
  getTendencia: (planId: number) =>
    ApiService.get(`/variantes/tendencia?plan_id=${planId}`),
  getMediciones: (indicadorId: number) =>
    ApiService.get<Medicion[]>(`/mediciones?indicador_id=${indicadorId}`),
};

export const EvaluacionService = {
  getMiEvaluacion: (periodo: string) =>
    ApiService.get<EvaluacionDesempeno>(`/evaluaciones/mi-evaluacion?periodo=${periodo}`),
  getRanking: (periodo: string) =>
    ApiService.get(`/ranking?periodo=${periodo}`),
};

export const IAService = {
  ask: (contexto: string, prompt: string) =>
    ApiService.post('/ia/asistencia', { contexto, prompt }),
  generarRecomendacion: (contexto: string, id: number) =>
    ApiService.post('/ia/recomendaciones', { contexto, contexto_id: id }),
  predecir: (indicadorId: number) =>
    ApiService.post(`/ia/predicciones/${indicadorId}`),
  generarContenido: (tipo: string, contexto: any) =>
    ApiService.post(`/ia/generar/${tipo}`, contexto),
};

export const DocService = {
  getNormas: (sectorId?: number) =>
    ApiService.get(`/normas-iso${sectorId ? `?sector_id=${sectorId}` : ''}`),
  getDocumentos: (empresaId: number) =>
    ApiService.get(`/documentos?empresa_id=${empresaId}`),
};

const ApiService = new ApiServiceClass();
export default ApiService;
