// Plik do dynamicznego ładowania galerii z panelu administracyjnego
document.addEventListener('DOMContentLoaded', function () {
	// Kontener galerii
	const portfolioGrid = document.getElementById('portfolio-grid')
	const paginationNumbers = document.querySelector('.pagination-numbers')
	const prevPageBtn = document.getElementById('prev-page')
	const nextPageBtn = document.getElementById('next-page')

	// Konfiguracja paginacji
	const itemsPerPage = 8
	let currentPage = 1
	let currentFilter = 'all'
	let galleryItems = []
	let filteredItems = []

	// Pobieranie danych galerii z API
	async function fetchGalleryItems() {
		try {
			const response = await fetch('gallery_api.php?action=get')
			if (!response.ok) {
				throw new Error('Nie udało się pobrać danych galerii')
			}

			const result = await response.json()
			if (result.success) {
				galleryItems = result.data || []
				filteredItems = [...galleryItems]
				initGallery()
			} else {
				console.error('Błąd podczas pobierania galerii:', result.error)
				// Fallback - pokaż przykładowe dane
				renderFallbackGallery()
			}
		} catch (error) {
			console.error('Błąd podczas ładowania galerii:', error)
			// Fallback - pokaż przykładowe dane
			renderFallbackGallery()
		}
	}

	// Inicjalizacja galerii
	function initGallery() {
		updateFilteredItems()
		renderGallery()
		setupFilters()
	}

	// Filtrowanie elementów
	function updateFilteredItems() {
		if (currentFilter === 'all') {
			filteredItems = [...galleryItems]
		} else if (currentFilter === 'special') {
			filteredItems = galleryItems.filter(item => item.category === 'special' || item.category === 'okolicznosciowe')
		} else {
			filteredItems = galleryItems.filter(item => item.category === currentFilter)
		}
		currentPage = 1
		updatePaginationControls()
	}

	// Generowanie elementu galerii
	function createGalleryItem(item) {
		const itemDiv = document.createElement('div')
		itemDiv.className = `portfolio-item ${item.category}`
		itemDiv.setAttribute('data-aos', 'fade-up')

		// Dodatkowe atrybuty jeśli jest to okolicznościowe
		if (item.category === 'special') {
			itemDiv.classList.add('okolicznosciowe')
			itemDiv.setAttribute('data-category', 'special')
		}

		itemDiv.innerHTML = `
            <div class="relative overflow-hidden rounded-lg group">
                <img
                    src="${item.image_src}"
                    alt="${item.alt_text || item.title}"
                    class="w-full h-auto transition-all duration-500 group-hover:scale-110"
                    loading="lazy"
                />
                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-70 transition-all duration-300 flex items-center justify-center">
                    <div class="text-white opacity-0 group-hover:opacity-100 transition-all duration-300 text-center px-6">
                        <h3 class="text-xl font-semibold mb-2">${item.title}</h3>
                        <p class="mb-4">${item.description}</p>
                        <a href="#" class="px-4 py-2 rounded-md text-white font-medium" style="background-color: var(--brown)">
                            Zobacz więcej
                        </a>
                    </div>
                </div>
            </div>
        `

		return itemDiv
	}

	// Renderowanie galerii
	function renderGallery() {
		// Wyczyść obecną zawartość
		portfolioGrid.innerHTML = ''

		const startIndex = (currentPage - 1) * itemsPerPage
		const endIndex = startIndex + itemsPerPage
		const currentItems = filteredItems.slice(startIndex, endIndex)

		if (currentItems.length === 0) {
			const noItemsMessage = document.createElement('div')
			noItemsMessage.className = 'col-span-full text-center py-10'
			noItemsMessage.innerHTML = '<p>Brak elementów do wyświetlenia w tej kategorii.</p>'
			portfolioGrid.appendChild(noItemsMessage)
			return
		}

		// Dodaj elementy do grida
		currentItems.forEach((item, index) => {
			const galleryItem = createGalleryItem(item)
			galleryItem.setAttribute('data-aos-delay', (index + 1) * 100)
			portfolioGrid.appendChild(galleryItem)
		})

		// Odśwież AOS jeśli jest używany
		if (typeof AOS !== 'undefined') {
			AOS.refresh()
		}
	}

	// Renderowanie fallbackowej galerii (gdy API zawiedzie)
	function renderFallbackGallery() {
		console.log('Używanie danych zastępczych dla galerii')
		portfolioGrid.innerHTML =
			'<p class="col-span-full text-center">Nie można załadować galerii. Skontaktuj się z administratorem.</p>'
	}

	// Aktualizacja kontrolek paginacji
	function updatePaginationControls() {
		const totalPages = Math.ceil(filteredItems.length / itemsPerPage)

		// Aktualizuj przyciski prev/next
		prevPageBtn.disabled = currentPage <= 1
		nextPageBtn.disabled = currentPage >= totalPages

		// Style dla przycisków wyłączonych
		if (prevPageBtn.disabled) {
			prevPageBtn.classList.add('opacity-50', 'cursor-not-allowed')
		} else {
			prevPageBtn.classList.remove('opacity-50', 'cursor-not-allowed')
		}

		if (nextPageBtn.disabled) {
			nextPageBtn.classList.add('opacity-50', 'cursor-not-allowed')
		} else {
			nextPageBtn.classList.remove('opacity-50', 'cursor-not-allowed')
		}

		// Generuj numery stron
		paginationNumbers.innerHTML = ''

		for (let i = 1; i <= totalPages; i++) {
			const pageBtn = document.createElement('button')
			pageBtn.innerText = i
			pageBtn.classList.add('w-8', 'h-8', 'rounded-full', 'flex', 'items-center', 'justify-center', 'transition-all')

			if (i === currentPage) {
				pageBtn.classList.add('bg-brown', 'text-white')
			} else {
				pageBtn.classList.add('border', 'border-brown', 'text-brown', 'hover:bg-brown', 'hover:text-white')
			}

			pageBtn.addEventListener('click', () => {
				currentPage = i
				renderGallery()
				updatePaginationControls()
			})

			paginationNumbers.appendChild(pageBtn)
		}
	}

	// Konfiguracja filtrów
	function setupFilters() {
		const filterButtons = document.querySelectorAll('.portfolio-filter-btn')

		filterButtons.forEach(function (button) {
			button.addEventListener('click', function () {
				// Usuń aktywną klasę ze wszystkich przycisków
				filterButtons.forEach(function (btn) {
					btn.classList.remove('active')
					btn.style.backgroundColor = ''
					btn.style.color = 'var(--brown)'
				})

				// Dodaj aktywną klasę do klikniętego przycisku
				this.classList.add('active')
				this.style.backgroundColor = 'var(--brown)'
				this.style.color = 'white'

				// Ustaw filtr i odśwież widok
				currentFilter = this.getAttribute('data-filter')
				updateFilteredItems()
				renderGallery()
			})
		})
	}

	// Event listenery dla przycisków paginacji
	prevPageBtn.addEventListener('click', () => {
		if (currentPage > 1) {
			currentPage--
			renderGallery()
			updatePaginationControls()
		}
	})

	nextPageBtn.addEventListener('click', () => {
		const totalPages = Math.ceil(filteredItems.length / itemsPerPage)
		if (currentPage < totalPages) {
			currentPage++
			renderGallery()
			updatePaginationControls()
		}
	})

	// Automatyczne załadowanie galerii przy starcie
	fetchGalleryItems()
})
