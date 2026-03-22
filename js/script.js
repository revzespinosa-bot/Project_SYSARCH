// Hero Image Animation
const img = document.querySelector(".hero-img");

if (img) {
    setInterval(() => {
        img.style.transform = "translateY(" + (Math.sin(Date.now() / 500) * 10) + "px)";
    }, 30);
}

function openModal() {
    const modal = document.getElementById("editModal");
    if (modal) modal.style.display = "block";
    const viewMode = document.getElementById("viewMode");
    const editMode = document.getElementById("editMode");
    const modalTitle = document.getElementById("modalTitle");
    if (viewMode) viewMode.style.display = "block";
    if (editMode) editMode.style.display = "none";
    if (modalTitle) modalTitle.textContent = "👤 Student Profile";
}

function closeModal() {
    const modal = document.getElementById("editModal");
    if (modal) modal.style.display = "none";
}

function toggleEdit() {
    const viewMode = document.getElementById("viewMode");
    const editMode = document.getElementById("editMode");
    const modalTitle = document.getElementById("modalTitle");
    
    if (viewMode && editMode && modalTitle) {
        if (viewMode.style.display === "none") {
            viewMode.style.display = "block";
            editMode.style.display = "none";
            modalTitle.textContent = "👤 Student Profile";
        } else {
            viewMode.style.display = "none";
            editMode.style.display = "block";
            modalTitle.textContent = "✏️ Edit Student Information";
        }
    }
}

// Admin Dashboard modals
function openFeature(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = "block";
}

function closeFeature(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = "none";
}

function scrollToSearch() {
    const section = document.getElementById("student-search");
    if (section) {
        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

window.onclick = function(event) {
    const editModal = document.getElementById("editModal");
    if (editModal && event.target == editModal) {
        closeModal();
    }

    const modals = document.querySelectorAll(".admin-modal");
    modals.forEach(function(modal) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    });
};

document.addEventListener("keydown", function(event) {
    if (event.key === "Escape") {
        closeModal();
        document.querySelectorAll('.admin-modal').forEach(function(modal){
            modal.style.display = 'none';
        });
    }
});