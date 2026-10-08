const t = document.getElementById("track");
t.innerHTML += t.innerHTML;
const toast = document.getElementById("toast");
document.querySelectorAll("[data-copy]").forEach((b) =>
    b.addEventListener("click", () => {
        navigator.clipboard && navigator.clipboard.writeText(b.dataset.copy);
        toast.classList.add("show");
        setTimeout(() => toast.classList.remove("show"), 1600);
    }),
);
