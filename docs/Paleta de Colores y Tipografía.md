# Paleta de Colores y Tipografía - GreenFashion

## Paleta de Colores

### Colores Principales
| Color | Código HEX | Uso |
|-------|------------|-----|
| Verde Natural | `#2E7D32` | Color principal de la marca, usado en elementos primarios y destacados |
| Verde Oscuro | `#1B5E20` | Variante oscura para hover y elementos secundarios |
| Blanco Puro | `#FFFFFF` | Fondo principal y texto sobre colores oscuros |
| Beige Natural | `#F5F5DC` | Fondo alternativo y elementos decorativos |
| Gris Claro | `#F0F0F0` | Fondos secundarios y hover de elementos neutros |

### Colores de Interfaz
| Color | Código HEX | Uso |
|-------|------------|-----|
| Gris Texto | `#333333` | Texto principal y elementos de UI oscuros |
| Gris Borde | `#DDDDDD` | Bordes y separadores |
| Rojo Error | `#D32F2F` | Mensajes de error y acciones destructivas |
| Verde Éxito | `#4CAF50` | Mensajes de éxito y confirmaciones |
| Azul Info | `#2196F3` | Enlaces y acciones informativas |
| Amarillo Advertencia | `#FFC107` | Mensajes de advertencia |

### Opacidades y Sombras
- Sombra Modal: `rgba(0, 0, 0, 0.15)`
- Overlay Modal: `rgba(0, 0, 0, 0.5)`
- Focus Shadow Verde: `rgba(46, 125, 50, 0.15)`
- Focus Shadow Error: `rgba(211, 47, 47, 0.15)`

## Tipografía

### Fuentes
- **Fuente Principal**: Sistema por defecto
  ```css
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
  ```

### Tamaños de Fuente
| Elemento | Tamaño | Peso | Uso |
|----------|--------|------|-----|
| Títulos H1 | `1.5em` | `600` | Títulos principales de página |
| Títulos H2 | `1.3em` | `600` | Títulos de secciones y modales |
| Texto Regular | `1em` | `400` | Texto general y contenido |
| Texto Pequeño | `0.95em` | `400` | Labels y texto secundario |
| Botones | `0.95em` | `600` | Texto en botones y acciones |

### Pesos de Fuente
- **Regular**: `400` - Texto general
- **Semibold**: `600` - Títulos, botones y elementos destacados
- **Bold**: `700` - Uso específico para máximo énfasis

### Interlineado
- Texto General: `1.5`
- Títulos: `1.2`
- Elementos UI: `1.3`

## Uso y Aplicación

### Variables CSS
```css
:root {
  /* Colores principales */
  --verde-natural: #2E7D32;
  --verde-oscuro: #1B5E20;
  --blanco-puro: #FFFFFF;
  --beige-natural: #F5F5DC;
  --gris-claro: #F0F0F0;

  /* Colores de interfaz */
  --gris-texto: #333333;
  --gris-borde: #DDDDDD;
  --rojo-error: #D32F2F;
  --verde-exito: #4CAF50;
  --azul-info: #2196F3;
  --amarillo-advertencia: #FFC107;
}
```

### Mejores Prácticas
1. Usar las variables CSS definidas en lugar de valores hardcodeados
2. Mantener consistencia en el uso de colores según su propósito
3. Respetar la jerarquía tipográfica establecida
4. Utilizar los pesos de fuente apropiados para cada contexto 