async function loadHotspots() {
    const response = await fetch('hotspots.json');
    const hotspots = await response.json();
  
    const svg = document.getElementById('hotspot-layer');
  
    hotspots.forEach(h => {
      const polygon = document.createElementNS("http://www.w3.org/2000/svg", "polygon");
      polygon.setAttribute("points", h.points);
      polygon.setAttribute("class", "hotspot");
      polygon.setAttribute("data-title", h.title);
      polygon.setAttribute("data-text", h.text);
      polygon.setAttribute("data-img", h.image);
  
      polygon.addEventListener("click", () => showDialog(h));
      svg.appendChild(polygon);
    });
  }
  
  function showDialog(hotspot) {
    document.getElementById("dialog-title").textContent = hotspot.title;
    document.getElementById("dialog-text").textContent = hotspot.text;
    document.getElementById("dialog-image").src = hotspot.image;
    document.getElementById("dialog").classList.add("active");
  }
  
  function closeDialog() {
    document.getElementById("dialog").classList.remove("active");
  }
  
  loadHotspots();
  