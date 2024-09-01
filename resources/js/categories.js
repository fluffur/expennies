import { Modal }          from "bootstrap"
import {get, post, del, clearValidationErrors} from "./ajax"
import DataTable          from "datatables.net"

window.addEventListener('DOMContentLoaded', function () {
    const editCategoryModal = new Modal(document.getElementById('editCategoryModal'))
    const newCategoryModal = new Modal(document.getElementById('newCategoryModal'))
    const table = new DataTable('#categoriesTable', {
        serverSide: true,
        ajax: '/categories/load',
        orderMulti: false,
        columns: [
            {data: "name"},
            {data: "createdAt"},
            {data: "updatedAt"},
            {
                sortable: false,
                data: row => `
                    <div class="d-flex flex-">
                        <button type="submit" class="btn btn-outline-primary delete-category-btn" data-id="${ row.id }">
                            <i class="bi bi-trash3-fill"></i>
                        </button>
                        <button class="ms-2 btn btn-outline-primary edit-category-btn" data-id="${ row.id }">
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                    </div>
                `
            }
        ]
    });


    document.querySelector('.create-category-btn').addEventListener('click', function (event) {

        post(`/categories`, {
            name: newCategoryModal._element.querySelector('input[name="name"]').value
        }, newCategoryModal._element).then(response => {
            if (response.ok) {
                table.draw()
                newCategoryModal.hide()
            }
        })
    });

    document.querySelector('#categoriesTable').addEventListener('click', function (event) {
        const editBtn   = event.target.closest('.edit-category-btn')
        const deleteBtn = event.target.closest('.delete-category-btn')

        if (editBtn) {
            clearValidationErrors(editCategoryModal._element);
            const categoryId = editBtn.getAttribute('data-id')

            get(`/categories/${ categoryId }`)
                .then(response => response.json())
                .then(response => openEditCategoryModal(editCategoryModal, response))
        } else if (deleteBtn) {
            const categoryId = deleteBtn.getAttribute('data-id')

            if (confirm('Are you sure you want to delete this category?')) {
                del(`/categories/${ categoryId }`).then(() => {
                    table.draw()
                })
            }
        } else {
   }
    })


    document.querySelector('.open-new-category-form-btn').addEventListener('click', function (event) {
        clearValidationErrors(newCategoryModal._element)
        newCategoryModal._element.querySelector('input[name="name"]').value = ''

    })

    document.querySelector('.save-category-btn').addEventListener('click', function (event) {
        const categoryId = event.currentTarget.getAttribute('data-id')

        post(`/categories/${ categoryId }`, {
            name: editCategoryModal._element.querySelector('input[name="name"]').value
        }, editCategoryModal._element).then(response => {
            if (response.ok) {
                table.draw()
                editCategoryModal.hide()
            }
        })
    })
})

function openEditCategoryModal(modal, {id, name}) {
    const nameInput = modal._element.querySelector('input[name="name"]')

    nameInput.value = name

    modal._element.querySelector('.save-category-btn').setAttribute('data-id', id)
    modal.show()
}
