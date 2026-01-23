# agendaNeto
# Sistema Corporativo de Gestión Organizacional (Laravel 12)

## 📌 Descripción General
[cite_start]Este sistema es una solución corporativa diseñada para operar sobre múltiples compañías con una estructura jerárquica estricta[cite: 4, 5]. [cite_start]La arquitectura separa rigurosamente la **Estructura** (definición de la organización) de la **Operación** (ejecución diaria)[cite: 71, 73].

## 🛠 Stack Tecnológico
* [cite_start]**Backend:** Laravel 12[cite: 21].
* [cite_start]**Autenticación/Equipos:** Laravel Jetstream con modo Teams habilitado (Team = Compañía)[cite: 21, 31].
* [cite_start]**Frontend:** Inertia.js + Vue.js (Composition API)[cite: 21].
* [cite_start]**Base de Datos:** MySQL / PostgreSQL[cite: 21].

## 🏗 Modelo Organizacional
[cite_start]El sistema se rige por la siguiente jerarquía operativa[cite: 9, 23]:
1.  [cite_start]**Compañía (Team):** Unidad principal de aislamiento de datos[cite: 24, 31].
2.  [cite_start]**Región:** División territorial dentro de una compañía[cite: 25].
3.  [cite_start]**Sucursal:** Punto operativo vinculado a una región y compañía[cite: 26].

## 🔐 Niveles de Acceso y Roles
[cite_start]Se implementan tres niveles de control jerárquico[cite: 33, 34]:

### Nivel 1: Control Global (Supervisor / Gerente de Área)
* [cite_start]**Alcance:** Todas las compañías (Multi-Team)[cite: 36, 62].
* [cite_start]**Funciones:** Administrar la estructura global, crear compañías y reasignar personal entre ellas[cite: 38, 43, 64].

### Nivel 2: Control por Compañía (Coordinador)
* [cite_start]**Alcance:** Solo su propia compañía asignada (Team Owner)[cite: 31, 45].
* [cite_start]**Funciones:** Gestionar ingenieros y sucursales dentro de su empresa[cite: 47, 49].

### Nivel 3: Operativo (Ingeniero de Sitio)
* [cite_start]**Alcance:** Región base y regiones de apoyo autorizadas[cite: 52, 68].
* [cite_start]**Funciones:** Registro de actividades, reportes y atención de tareas[cite: 55, 56, 57].

## ⚙️ Principios de Diseño
* [cite_start]**Aislamiento de Datos:** Cada compañía opera de forma independiente mediante el uso de Teams[cite: 18, 93].
* [cite_start]**Excepciones de Apoyo:** Los ingenieros pueden operar en regiones adicionales sin romper la jerarquía base mediante una relación de "Apoyo"[cite: 69, 70].
* [cite_start]**Estructura ≠ Operación:** Los cambios en la estructura son críticos y controlados por los niveles 1 y 2, mientras que la operación es el flujo diario de los ingenieros[cite: 73, 83, 91].

## 🚀 Próximos Pasos
1.  [cite_start]Implementación de migraciones basadas en el diagrama ER[cite: 98].
2.  [cite_start]Definición de Policies y Gates para control de acceso[cite: 99].
3.  [cite_start]Normalización e importación de datos desde fuentes externas[cite: 100].
4.  [cite_start]Diseño de interfaces diferenciadas por rol en Vue 3[cite: 101].