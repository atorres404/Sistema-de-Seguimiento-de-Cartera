# 📊 Sistema de Recuperaciones de Crédito

Sistema web integral para la gestión y seguimiento de cartera de créditos, diseñado para facilitar el control de recuperaciones, análisis de saldos y generación de reportes financieros.

![Sistema de Recuperaciones](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-orange.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

## 📋 Tabla de Contenidos

- [Características](#-características)
- [Tecnologías](#-tecnologías)
- [Requisitos](#-requisitos)
- [Instalación](#-instalación)
- [Configuración](#-configuración)
- [Uso](#-uso)
- [Estructura del Proyecto](#-estructura-del-proyecto)
- [Base de Datos](#-base-de-datos)
- [Credenciales de Prueba](#-credenciales-de-prueba)
- [Capturas de Pantalla](#-capturas-de-pantalla)
- [Contribuir](#-contribuir)
- [Licencia](#-licencia)

## ✨ Características

### Gestión de Usuarios
- ✅ Sistema de autenticación con roles (Administrador/Usuario)
- ✅ Encriptación de contraseñas con SHA-512
- ✅ Control de acceso basado en roles

### Dashboard Interactivo
- 📊 Visualización de estadísticas
- 📈 Gráficas comparativas por entidad financiera
- 💰 Totales de créditos colocados, vigentes y por recuperar
- 📉 Análisis de intereses acumulados

### Gestión de Contratos
- 📄 Carga masiva de datos mediante archivos Excel
- 🔍 Filtros avanzados (saldo vigente, rango de fechas)
- 📑 Reportes detallados por empresa
- 🎯 Seguimiento de vencimientos

### Visualización de Datos
- 🎨 Gráficas de barras por año fiscal
- 🥧 Gráficas circulares de distribución de recuperación
- 📊 Indicadores visuales con porcentajes
- 💹 Comparativas entre entidades

### Backend
- **PHP 7.4+** - Lenguaje de servidor
- **MySQL 8.0+** - Base de datos relacional
- **PhpSpreadsheet** - Procesamiento de archivos Excel

### Frontend
- **HTML5** - Estructura
- **CSS3** - Estilos modernos con gradientes y animaciones
- **JavaScript ES6** - Interactividad
- **Chart.js** - Visualización de gráficas
- **Font Awesome** - Iconografía

## 📦 Requisitos

- PHP >= 7.4
- MySQL >= 8.0
- Apache Server
- Composer
- Extensiones PHP:
  - mysqli
  - zip
  - xml
  - gd

## 🚀 Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/atorres404/sistema-de-seguimiento-de-cartera.git
cd sistema-recuperaciones
```

### 2. Instalar dependencias

```bash
composer install
```

### 3. Configurar la base de datos

1. Importar el archivo SQL:
```bash
# Acceder a phpMyAdmin o MySQL CLI
mysql -u root -p < database/sistema_recuperaciones_db.sql
```

2. O desde phpMyAdmin:
   - Crear base de datos: `sistema_recuperaciones_db`
   - Importar: `database/sistema_recuperaciones_db.sql`

### 4. Configurar conexión

Editar `php/conexion_be.php`:

```php
$conexion = mysqli_connect("localhost", "root", "", "sistema_recuperaciones_db");
```

### 5. Iniciar el servidor

```bash
# Si usas XAMPP, coloca el proyecto en:
C:\xampp\htdocs\sistema-recuperaciones\

# Acceder desde el navegador:
http://localhost/sistema-recuperaciones/
```

## ⚙️ Configuración

### Zona Horaria

El sistema está configurado para `America/Mexico_City`. Para cambiar:

```php
// En archivos PHP que lo requieran
date_default_timezone_set('America/Mexico_City');
```

### Tamaño de archivos Excel

Configurar en `php.ini`:

```ini
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
```

### Inicio de Sesión

1. Acceder a `http://localhost/sistema-recuperaciones/`
2. Ingresar credenciales (ver sección de credenciales de prueba)

### Cargar Datos Excel (Solo Administrador)

1. Ir a **Subir Archivos Excel**
2. Seleccionar archivo para "Dispersora de Crédito"
3. Seleccionar archivo para "Financiera SOFOM"
4. Click en **Cargar y Procesar**

**Formato requerido del Excel:**
- Columna A: Razón Social
- Columna B: Número de Contrato
- Columna C: Importe Ministrado
- Columna D: Saldo
- Columna E: Intereses
- Columna F: Vencimiento (formato fecha)

### Consultar Reportes

1. Ir a **Reportes**
2. Seleccionar empresa (Dispersora/SOFOM)
3. Aplicar filtros según necesidad:
   - Saldo vigente (Sí/No/Todos)
   - Rango de fechas
4. Ver tabla detallada y gráficas

### Dashboard

El dashboard muestra automáticamente:
- Total de créditos colocados
- Importe ministrado
- Saldo por recuperar
- Intereses acumulados
- Gráficas comparativas por año

## 📁 Estructura del Proyecto

```
sistema-recuperaciones/
│
├── assets/
│   ├── images/
│   │   ├── logo.png
│   │   └── bg4.jpeg
│   └── ...
│
├── db/
│   └── sistema_recuperaciones_db.sql
│
├── php/
│   ├── conexion_be.php           # Conexión a BD
│   ├── login_usuario_be.php      # Lógica de login
│   └── cerrar_sesion.php         # Cerrar sesión
│
├── vendor/                        # Dependencias Composer
│
├── index.php                      # Página de login
├── inicio.php                     # Dashboard principal
├── ver_datos.php                  # Reportes y consultas
├── subir_excel.php                # Interfaz de carga
├── procesar_excel.php             # Procesamiento de Excel
├── composer.json                  # Dependencias PHP
└── README.md                      # Documentación
```

## 🗄️ Base de Datos

### Tabla: `contratos`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT | ID único autoincremental |
| razon_social | VARCHAR(255) | Nombre del acreditado |
| numero_contrato | VARCHAR(100) | Número de contrato |
| importe_ministrado | DECIMAL(15,2) | Monto original del crédito |
| saldo | DECIMAL(15,2) | Saldo pendiente |
| intereses | DECIMAL(15,2) | Intereses acumulados |
| vencimiento | DATE | Fecha de vencimiento |
| empresa | VARCHAR(50) | Entidad (DISPERSORA_CREDITO/FINANCIERA_SOFOM) |
| ultima_actualizacion | TIMESTAMP | Fecha de última actualización |

### Tabla: `usuarios`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT | ID único autoincremental |
| nombre_completo | VARCHAR(50) | Nombre del usuario |
| correo | VARCHAR(50) | Email (único) |
| usuario | VARCHAR(50) | Username (único) |
| contrasena | VARCHAR(150) | Contraseña hasheada SHA-512 |
| rol | ENUM | admin/usuario |

## 🔑 Credenciales de Prueba

### Administrador
- **Correo:** admin@sistema.com
- **Contraseña:** Admin123
- **Permisos:** Carga de archivos, acceso completo

### Usuario Normal
- **Correo:** usuario@sistema.com
- **Contraseña:** User123
- **Permisos:** Solo consulta y reportes

⚠️ **Importante:** Cambiar estas credenciales en producción

## 📸 Capturas de Pantalla
<img width="1916" height="930" alt="image" src="https://github.com/user-attachments/assets/2a0d4902-0970-4c7e-afc0-1f5809080466" />
<img width="1852" height="903" alt="image" src="https://github.com/user-attachments/assets/f6ea9888-68bc-49af-a113-4a34f66fa59f" />
<img width="1827" height="861" alt="image" src="https://github.com/user-attachments/assets/f2417f1f-c37d-4a14-bcb9-443ea5493da8" />
<img width="1821" height="868" alt="image" src="https://github.com/user-attachments/assets/fca47136-e7f0-490c-bd74-478a0b2a341d" />
<img width="1818" height="850" alt="image" src="https://github.com/user-attachments/assets/5382e75c-b2bc-47e9-8c6b-3798b4373bb7" />

<img width="1908" height="928" alt="image" src="https://github.com/user-attachments/assets/8232c464-87b5-4055-ab3c-a0512cfdb74d" />


### Datos de Ejemplo

La base de datos incluye 40 contratos de ejemplo con datos ficticios para propósitos de demostración.

### Seguridad

- Las contraseñas se hashean con SHA-512
- Uso de `mysqli_real_escape_string` para prevenir SQL Injection
- Validación de roles en cada página protegida
- Sesiones PHP seguras

### Mejoras Futuras

- [ ] Exportación de reportes a PDF
- [ ] Envío de notificaciones por correo
- [ ] Panel de analytics avanzado
- [ ] API REST para integraciones
- [ ] Sistema de bitácora de cambios
- [ ] Recordatorios de vencimientos
- [ ] Otras mas...

## 👨‍💻 Autor

- GitHub: [atorres404](https://github.com/atorres404)
- Email: alantrrzs4@gmail.com

## 🙏 Agradecimientos

- Chart.js por las librerías de gráficas
- PhpSpreadsheet por el procesamiento de Excel
- A ti por tu visita

**Desarrollado con ❤️ para el seguimiento de crédito**
