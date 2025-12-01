import { Controller } from "@hotwired/stimulus";

// Connects to data-controller="artist-products"
export default class extends Controller {
  static targets = ["productSelect", "productContainer"];
  static values = { url: String };

  connect() {
    // Listen to any change on artist radios within this element
    this.element.addEventListener("change", (e) => {
      const input = e.target;
      if (input && input.name && input.name.endsWith("[artist]") && input.checked) {
        this.loadProductsFor(input.value);
      }
    });
  }

  async loadProductsFor(artistId) {
    if (!artistId || !this.hasProductSelectTarget) return;
    try {
      const url = new URL(this.urlValue, window.location.origin);
      url.searchParams.set("id", artistId);
      const resp = await fetch(url.toString(), { headers: { Accept: "application/json" } });
      const data = await resp.json();

      // Clear existing options
      const select = this.productSelectTarget;
      select.innerHTML = "";

      if (Array.isArray(data) && data.length) {
        // Placeholder option
        const ph = document.createElement("option");
        ph.value = "";
        ph.textContent = "Sélectionnez un produit";
        select.appendChild(ph);

        for (const item of data) {
          const opt = document.createElement("option");
          opt.value = item.id;
          opt.textContent = item.label;
          select.appendChild(opt);
        }
        select.disabled = false;
        if (this.hasProductContainerTarget) this.productContainerTarget.style.display = "block";
      } else {
        const ph = document.createElement("option");
        ph.value = "";
        ph.textContent = "Aucun produit pour cet artiste";
        select.appendChild(ph);
        select.disabled = true;
        if (this.hasProductContainerTarget) this.productContainerTarget.style.display = "block";
      }
    } catch (e) {
      console.error("Failed to load products:", e);
    }
  }
}
