# 👥 DEFINICIÓN DE ROLES DE USUARIO - GREENFASHION
## SISTEMA DE MODA SOSTENIBLE

---

## 1. 📋 Información General

### 1.1 Objetivo del Documento
Este documento define los roles de usuario, sus permisos, responsabilidades y funcionalidades específicas dentro del sistema GreenFashion.

### 1.2 Alcance
- **Tipos de usuarios:** Cliente y Administrador
- **Nivel de acceso:** Funcionalidades básicas y avanzadas
- **Seguridad:** Permisos y restricciones por rol

---

## 2. 🛍️ ROL: CLIENTE (USUARIO FINAL)

### 2.1 Descripción
El **Cliente** es el usuario final del sistema, representa a los consumidores que buscan productos de moda sostenible. Su objetivo principal es navegar, comprar y gestionar sus pedidos de manera autónoma.

### 2.2 Características del Usuario
- **Edad:** 18-45 años
- **Intereses:** Moda sostenible y ecológica
- **Nivel técnico:** Básico a intermedio
- **Objetivo:** Comprar productos de moda sostenible

### 2.3 ✅ Permisos y Funcionalidades

#### 2.3.1 🔐 Gestión de Cuenta
- **Registro:** Crear nueva cuenta de usuario
- **Login/Logout:** Iniciar y cerrar sesión
- **Perfil:** Editar datos personales (nombre, email, teléfono)
- **Contraseña:** Cambiar contraseña personal
- **Direcciones:** Gestionar direcciones de envío

#### 2.3.2 🛒 Funcionalidades de Compra
- **Catálogo:** Visualizar todos los productos disponibles
- **Filtros:** Aplicar filtros por categoría, precio, talla, color
- **Búsqueda:** Buscar productos específicos
- **Detalles:** Ver información detallada de productos
- **Carrito:** Agregar/remover productos del carrito
- **Cantidades:** Modificar cantidades en el carrito
- **Checkout:** Procesar pago y finalizar compra
- **Descuentos:** Aplicar códigos de descuento válidos

#### 2.3.3 📦 Gestión de Pedidos
- **Historial:** Consultar historial de compras
- **Estado:** Verificar estado de pedidos actuales
- **Confirmación:** Recibir confirmaciones por email
- **Seguimiento:** Rastrear pedidos en proceso

#### 2.3.4 ⭐ Sistema de Reseñas
- **Calificar:** Dar puntuación (1-5 estrellas) a productos comprados
- **Reseñas:** Escribir reseñas de productos adquiridos
- **Consultar:** Leer reseñas de otros usuarios
- **Verificación:** Solo puede reseñar productos comprados

#### 2.3.5 🌿 Información Sostenible
- **Materiales:** Consultar información sobre materiales ecológicos
- **Procesos:** Leer sobre procesos de producción sostenible
- **Educación:** Acceder a contenido educativo sobre sostenibilidad

### 2.4 🚫 Restricciones
- **NO puede:** Acceder al panel administrativo
- **NO puede:** Modificar productos o precios
- **NO puede:** Gestionar otros usuarios
- **NO puede:** Generar reportes del sistema
- **NO puede:** Moderar contenido de otros usuarios

---

## 3. 🔧 ROL: ADMINISTRADOR

### 3.1 Descripción
El **Administrador** es el usuario con privilegios elevados que gestiona el sistema completo. Tiene acceso a todas las funcionalidades del sistema y es responsable de mantener el catálogo, usuarios y configuraciones.

### 3.2 Características del Usuario
- **Perfil:** Empleado de GreenFashion
- **Responsabilidad:** Gestión completa del sistema
- **Nivel técnico:** Intermedio a avanzado
- **Objetivo:** Mantener y administrar la plataforma

### 3.3 ✅ Permisos y Funcionalidades Completas

#### 3.3.1 🔐 Gestión de Cuenta (Hereda de Cliente)
- **Todas las funcionalidades del cliente**
- **Perfil Admin:** Acceso a configuraciones administrativas
- **Sesiones:** Gestión de sesiones administrativas

#### 3.3.2 🛍️ Gestión de Productos
- **CRUD Completo:** Crear, leer, actualizar y eliminar productos
- **Categorías:** Gestionar categorías de productos
- **Inventario:** Controlar stock y disponibilidad
- **Precios:** Modificar precios y ofertas
- **Imágenes:** Subir y gestionar galería de imágenes
- **Descripción:** Editar información de productos
- **Sostenibilidad:** Actualizar información ecológica

#### 3.3.3 👥 Administración de Usuarios
- **Visualizar:** Lista completa de usuarios registrados
- **Buscar:** Encontrar usuarios específicos
- **Editar:** Modificar datos de usuarios
- **Desactivar:** Suspender cuentas de usuario
- **Activar:** Reactivar cuentas suspendidas
- **Roles:** Asignar roles de usuario

#### 3.3.4 📦 Gestión de Pedidos
- **Todos los pedidos:** Visualizar pedidos de todos los usuarios
- **Estado:** Actualizar estado de pedidos
- **Procesamiento:** Marcar pedidos como procesados
- **Envío:** Gestionar información de envíos
- **Cancelación:** Cancelar pedidos si es necesario

#### 3.3.5 ⭐ Moderación de Contenido
- **Reseñas:** Moderar reseñas de productos
- **Aprobar:** Aprobar contenido de usuarios
- **Eliminar:** Remover contenido inapropiado
- **Reportes:** Gestionar reportes de contenido

#### 3.3.6 📊 Reportes y Análisis
- **Ventas:** Generar reportes de ventas
- **Productos:** Análisis de productos más vendidos
- **Usuarios:** Estadísticas de usuarios activos
- **Inventario:** Reportes de stock
- **Ingresos:** Análisis financiero básico

#### 3.3.7 ⚙️ Configuración del Sistema
- **Categorías:** Crear y gestionar categorías
- **Descuentos:** Crear códigos de descuento
- **Contenido:** Gestionar contenido educativo
- **Configuración:** Parámetros del sistema

### 3.4 🔒 Responsabilidades de Seguridad
- **Backup:** Realizar copias de seguridad periódicas
- **Monitoreo:** Supervisar actividad sospechosa
- **Mantenimiento:** Actualizar sistema regularmente
- **Privacidad:** Proteger datos de usuarios

---

## 4. 🔐 Matriz de Permisos

| Funcionalidad | Cliente | Administrador |
|---------------|---------|---------------|
| **Registro/Login** | ✅ | ✅ |
| **Ver productos** | ✅ | ✅ |
| **Comprar productos** | ✅ | ✅ |
| **Gestionar carrito** | ✅ | ✅ |
| **Escribir reseñas** | ✅ | ✅ |
| **Ver historial personal** | ✅ | ✅ |
| **Crear productos** | ❌ | ✅ |
| **Editar productos** | ❌ | ✅ |
| **Eliminar productos** | ❌ | ✅ |
| **Gestionar usuarios** | ❌ | ✅ |
| **Moderar contenido** | ❌ | ✅ |
| **Generar reportes** | ❌ | ✅ |
| **Configurar sistema** | ❌ | ✅ |
| **Panel administrativo** | ❌ | ✅ |

---

## 5. 🚀 Flujos de Trabajo por Rol

### 5.1 🛍️ Flujo Cliente - Proceso de Compra
```
1. Registro/Login
2. Navegación por catálogo
3. Filtros y búsqueda
4. Selección de producto
5. Agregar al carrito
6. Revisar carrito
7. Proceso de pago
8. Confirmación de pedido
9. Seguimiento de pedido
10. Recepción y reseña
```

### 5.2 🔧 Flujo Administrador - Gestión de Producto
```
1. Login administrativo
2. Acceso al panel admin
3. Gestión de productos
4. Crear/editar producto
5. Subir imágenes
6. Configurar precios
7. Gestionar inventario
8. Publicar producto
9. Monitorear ventas
10. Generar reportes
```

---

## 6. 🔒 Consideraciones de Seguridad

### 6.1 Autenticación
- **Contraseñas:** Encriptación segura
- **Sesiones:** Gestión de sesiones activas
- **Tiempo:** Expiración automática de sesiones

### 6.2 Autorización
- **Verificación:** Validación de permisos en cada acción
- **Roles:** Verificación de rol antes de acceder a funcionalidades
- **Restricciones:** Aplicación estricta de limitaciones por rol

### 6.3 Protección de Datos
- **Datos personales:** Protección de información del cliente
- **Historial:** Seguridad en historial de compras
- **Pagos:** Protección de información de pago

---

## 7. 📋 Historias de Usuario por Rol

### 7.1 Cliente
- **UH11:** Como cliente, quiero registrarme para crear una cuenta
- **UH12:** Como cliente, quiero iniciar sesión para acceder a mi cuenta
- **UH13:** Como cliente, quiero actualizar mi perfil para mantener mis datos actualizados
- **UH14:** Como cliente, quiero consultar mi historial de compras
- **UH15:** Como cliente, quiero gestionar mis direcciones de envío
- **UH19:** Como cliente, quiero escribir reseñas de productos comprados
- **UH20:** Como cliente, quiero calificar productos que he comprado

### 7.2 Administrador
- **UH04:** Como administrador, quiero gestionar el inventario
- **UH05:** Como administrador, quiero agregar/editar productos
- **UH22:** Como administrador, quiero moderar contenido de reseñas
- **UH23:** Como administrador, quiero acceder al panel administrativo
- **UH24:** Como administrador, quiero generar reportes básicos
- **UH25:** Como administrador, quiero administrar usuarios
- **UH26:** Como administrador, quiero gestionar contenido del sistema

---

*Documento generado para **GreenFashion** - Sistema de Moda Sostenible* 🌱 