// Hero Image Animation
const img = document.querySelector(".hero-img");

if(img){
    setInterval(()=>{
        img.style.transform = 
        "translateY(" + (Math.sin(Date.now()/500)*10) + "px)";
    },30);
}

// Modal Functions
function openModal() {
    document.getElementById("editModal").style.display = "block";
}

function closeModal() {
    document.getElementById("editModal").style.display = "none";
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById("editModal");
    if (event.target == modal) {
        closeModal();
    }
}

// Close modal with ESC key
document.addEventListener("keydown", function(event) {
    if (event.key === "Escape") {
        closeModal();
    }
});