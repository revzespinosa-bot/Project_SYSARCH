// --- Contents of script.js ---
const img = document.querySelector(".hero-img");

if(img){
    setInterval(()=>{
        img.style.transform = 
        "translateY(" + (Math.sin(Date.now()/500)*10) + "px)";
    },30);
}