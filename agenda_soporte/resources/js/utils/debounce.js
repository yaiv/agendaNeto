/**
 * Retrasa la ejecución de una función hasta que pasen X milisegundos
 * sin que se vuelva a llamar
 * 
 * @param {Function} func - Función a ejecutar
 * @param {Number} wait - Tiempo de espera en milisegundos (default: 300ms)
 * @returns {Function}
 */
export function debounce(func, wait = 300) {
  let timeout;
  
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}