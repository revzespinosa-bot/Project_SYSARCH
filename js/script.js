// Hero Image Animation
const img = document.querySelector(".hero-img");

if (img) {
    setInterval(() => {
        img.style.transform = "translateY(" + (Math.sin(Date.now() / 500) * 10) + "px)";
    }, 30);
}

// Open Modal (for edit profile)
function openModal() {
    const modal = document.getElementById("editModal");
    if (modal) {
        modal.style.display = "block";
        document.body.style.overflow = "hidden";
    }
    const viewMode = document.getElementById("viewMode");
    const editMode = document.getElementById("editMode");
    const modalTitle = document.getElementById("modalTitle");
    if (viewMode) viewMode.style.display = "block";
    if (editMode) editMode.style.display = "none";
    if (modalTitle) modalTitle.textContent = "👤 Student Profile";
}

// Close Modal (for edit profile)
function closeModal() {
    const modal = document.getElementById("editModal");
    if (modal) {
        modal.style.display = "none";
        document.body.style.overflow = "auto";
    }
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
    const modals = document.querySelectorAll(".modal, .admin-modal");
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
        document.querySelectorAll('.modal, .admin-modal').forEach(function(modal){
            modal.style.display = 'none';
        });
        document.body.style.overflow = "auto";
    }
});

// Auto-open search modal if search parameter exists
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('search')) {
        openFeature('searchModal');
    }
});

// ===== FEEDBACK & RATING FUNCTIONS =====
// Open feedback modal with history ID
function openFeedbackModal(historyId) {
    const modal = document.getElementById("feedbackModal");
    const historyIdField = document.getElementById("feedback_history_id");
    
    if (modal && historyIdField) {
        historyIdField.value = historyId;
        modal.style.display = "block";
        document.body.style.overflow = "hidden";
        
        // Reset rating display
        for (let i = 1; i <= 5; i++) {
            const btn = document.getElementById("starbtn_" + i);
            if (btn) {
                btn.style.color = "#ccc";
            }
        }
        
        // Reset hidden rating field
        document.getElementById("feedback_rating").value = "0";
        
        console.log("Feedback modal opened for history ID:", historyId);
    }
}

// Set rating when clicking a star
function setRating(rating) {
    // Update hidden rating field
    document.getElementById("feedback_rating").value = rating;
    
    // Update star display (1-5 stars only)
    for (let i = 1; i <= 5; i++) {
        const btn = document.getElementById("starbtn_" + i);
        if (btn) {
            btn.style.color = i <= rating ? "#fbbf24" : "#ccc";
            btn.style.fontSize = i <= rating ? "36px" : "32px";
            btn.style.transition = "all 0.2s";
        }
    }
    
    console.log("Rating set to:", rating);
}