// assets/app.js
import './styles/app.scss';
import { Modal } from 'bootstrap'; 

document.addEventListener("DOMContentLoaded", function () {
  const btn = document.getElementById("btn-reserver");
  const modalEl = document.getElementById("reservationModal");

  if (btn && modalEl) {
    const modal = new Modal(modalEl); 
    btn.addEventListener("click", function () {
      modal.show();
    });
  }
});
