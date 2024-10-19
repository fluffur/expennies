import { post } from './ajax';
import {Modal} from "bootstrap";

window.addEventListener('DOMContentLoaded', function () {
    const saveProfileBtn = document.querySelector('.save-profile')

    saveProfileBtn.addEventListener('click', function () {
        const form     = this.closest('form')
        const formData = new FormData(form);
        const data     = Object.fromEntries(formData.entries());

        saveProfileBtn.classList.add('disabled')

        post('/profile', data, form).then(response => {
            saveProfileBtn.classList.remove('disabled')

            if (response.ok) {
                alert('Profile has been updated.');
            }
        }).catch(() => {
            saveProfileBtn.classList.remove('disabled')
        })
    })


    const updatePasswordModal     = new Modal(document.getElementById('updatePasswordModal'))
    const updatePasswordBtn = updatePasswordModal._element.querySelector('.update-password');
    updatePasswordBtn.addEventListener('click', function () {
        const currentPassword = updatePasswordModal._element.querySelector('#currentPassword').value;
        const newPassword = updatePasswordModal._element.querySelector('#newPassword').value;
        const data     = {'currentPassword': currentPassword, 'newPassword': newPassword};
        post('/profile/change-password', data, updatePasswordModal._element)
            .then(response => {
                if (response.ok) {
                    updatePasswordModal.hide()
                }
            })
    });
})
