# Guía de Estilo Básica - GreenFashion

## Principios de Diseño

### 1. Consistencia
- Mantener espaciado consistente entre elementos (múltiplos de 8px)
- Usar los mismos estilos de bordes y radios en elementos similares
- Aplicar la misma paleta de colores en toda la aplicación

### 2. Jerarquía Visual
- Usar tamaños y pesos de fuente para establecer jerarquía
- Mantener un contraste adecuado entre elementos
- Implementar espaciado que refleje relaciones entre elementos

### 3. Feedback Visual
- Proporcionar estados hover y focus claros
- Mostrar mensajes de éxito/error con colores apropiados
- Usar animaciones sutiles para transiciones

## Componentes UI

### Botones
```css
/* Estilos base */
.btn {
    padding: 12px 24px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.95em;
    transition: all 0.3s ease;
}

/* Variantes */
.btn-primary {
    background: var(--verde-natural);
    color: var(--blanco-puro);
}

.btn-secondary {
    background: var(--blanco-puro);
    border: 2px solid var(--gris-borde);
    color: var(--gris-texto);
}

.btn-danger {
    background: var(--rojo-error);
    color: var(--blanco-puro);
}
```

### Inputs y Forms
```css
/* Contenedor del grupo de form */
.form-group {
    margin-bottom: 20px;
    position: relative;
}

/* Etiquetas */
.form-label {
    display: block;
    margin-bottom: 8px;
    color: var(--gris-texto);
    font-weight: 600;
    font-size: 0.95em;
}

/* Inputs */
.form-input {
    width: 100%;
    height: 48px;
    padding: 12px 16px;
    border: 2px solid var(--gris-borde);
    border-radius: 6px;
    font-size: 1em;
    transition: all 0.3s ease;
}
```

### Modales
```css
.modal {
    background: rgba(0, 0, 0, 0.5);
    padding: 20px;
}

.modal-content {
    background: var(--blanco-puro);
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}
```

## Espaciado y Layout

### Sistema de Grid
- Usar CSS Grid para layouts principales
- Flexbox para alineaciones y componentes más pequeños
- Mantener márgenes consistentes en contenedores (20px - 30px)

### Espaciado
| Tamaño | Uso |
|--------|-----|
| 8px | Espaciado mínimo entre elementos relacionados |
| 16px | Espaciado estándar entre elementos |
| 24px | Espaciado entre secciones |
| 32px | Espaciado grande para separar contenido |
| 48px | Espaciado extra grande para secciones principales |

### Breakpoints Responsive
```css
/* Mobile */
@media (max-width: 600px) {
    /* Ajustes para móvil */
}

/* Tablet */
@media (min-width: 601px) and (max-width: 960px) {
    /* Ajustes para tablet */
}

/* Desktop */
@media (min-width: 961px) {
    /* Ajustes para desktop */
}
```

## Estados y Feedback

### Estados de Hover
- Botones: Cambio sutil de color y elevación
- Links: Cambio de color y subrayado
- Elementos interactivos: Cursor pointer

### Estados de Focus
- Outline personalizado con color de marca
- Ring focus con opacidad para accesibilidad
- Alto contraste para elementos importantes

### Mensajes de Estado
```css
.mensaje {
    padding: 16px;
    border-radius: 6px;
    margin-bottom: 16px;
}

.mensaje-exito {
    background: rgba(76, 175, 80, 0.1);
    border: 1px solid var(--verde-exito);
    color: var(--verde-exito);
}

.mensaje-error {
    background: rgba(211, 47, 47, 0.1);
    border: 1px solid var(--rojo-error);
    color: var(--rojo-error);
}
```

## Mejores Prácticas

### Accesibilidad
1. Usar elementos semánticos HTML
2. Incluir estados focus visibles
3. Proporcionar textos alternativos para imágenes

### Performance
1. Minimizar uso de sombras y efectos pesados
2. Optimizar transiciones para rendimiento
3. Usar sistema de fuentes del sistema

### Mantenimiento
1. Mantener archivos CSS modulares
2. Documentar componentes nuevos
3. Actualizar guía de estilo con cambios 