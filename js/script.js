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
    if (modal) {
        modal.style.display = "block";
        document.body.style.overflow = "hidden"; // Prevent background scroll
    }
}

function closeFeature(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = "none";
        document.body.style.overflow = "auto"; // Restore scroll
    }
}

// ✅ FIXED: Sit-in Form Function
function openSitInForm(id, name) {
    console.log("✅ Sit-in clicked! ID:", id, "Name:", name); // Debug log
    
    const modal = document.getElementById("sitInFormModal");
    const idField = document.getElementById("sitin_id");
    const nameField = document.getElementById("sitin_name");
    
    if (!modal) {
        console.error("❌ sitInFormModal not found!");
        alert("Sit-in form not found. Please refresh the page.");
        return;
    }
    
    if (!idField || !nameField) {
        console.error("❌ Form fields not found!");
        alert("Form fields missing. Please refresh the page.");
        return;
    }
    
    // Fill the form
    idField.value = id;
    nameField.value = name;
    
    // Show modal
    modal.style.display = "block";
    document.body.style.overflow = "hidden";
    
    console.log("✅ Sit-in form opened successfully!");
}

// Close modals when clicking outside
window.onclick = function(event) {
    const modals = document.querySelectorAll(".admin-modal");
    modals.forEach(function(modal) {
        if (event.target === modal) {
            modal.style.display = "none";
            document.body.style.overflow = "auto";
        }
    });
};

// ESC key to close modals
document.addEventListener("keydown", function(event) {
    if (event.key === "Escape") {
        document.querySelectorAll('.admin-modal').forEach(function(modal){
            modal.style.display = 'none';
        });
        document.body.style.overflow = "auto";
    }
});

// Auto-open search modal if search parameter exists
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($search)) : ?>
        openFeature('searchModal');
    <?php endif; ?>
});