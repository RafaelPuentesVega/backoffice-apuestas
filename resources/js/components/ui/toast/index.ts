// Un objeto simple para mostrar alertas usando SweetAlert
import Swal from 'sweetalert2';

export const Toast = {
  success: (message: { title: string; description?: string }) => {
    Swal.fire({
      title: message.title,
      text: message.description,
      icon: 'success',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true
    });
  },
  error: (message: { title: string; description?: string }) => {
    Swal.fire({
      title: message.title,
      text: message.description,
      icon: 'error',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true
    });
  },
  warning: (message: { title: string; description?: string }) => {
    Swal.fire({
      title: message.title,
      text: message.description,
      icon: 'warning',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true
    });
  },
  info: (message: { title: string; description?: string }) => {
    Swal.fire({
      title: message.title,
      text: message.description,
      icon: 'info',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true
    });
  },
  // Añadimos la función loading que devuelve un objeto con un método dismiss
  loading: (message: { title: string; description?: string }) => {
    const toast = Swal.fire({
      title: message.title,
      text: message.description,
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      didOpen: (toast) => {
        Swal.showLoading();
      }
    });

    // Devolver un objeto con método dismiss para cerrar el toast manualmente
    return {
      dismiss: () => {
        Swal.close();
      }
    };
  }
}; 