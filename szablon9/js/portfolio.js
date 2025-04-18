document.addEventListener('DOMContentLoaded', function () {
	const grid = document.querySelector('.portfolio-grid')
	const filterBtns = document.querySelectorAll('.portfolio-filter button')
	const itemsPerPage = 8
	let currentPage = 1
	let items = []

	// Fetch portfolio items from API
	fetch('/api/get-portfolio-items.php')
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				items = data.items
				updateDisplay('all')
			} else {
				console.error('Error fetching items:', data.message)
			}
		})
		.catch(error => console.error('Error:', error))

	function updateDisplay(category) {
		const filteredItems = category === 'all' ? items : items.filter(item => item.category === category)

		const totalPages = Math.ceil(filteredItems.length / itemsPerPage)
		const start = (currentPage - 1) * itemsPerPage
		const end = start + itemsPerPage
		const currentItems = filteredItems.slice(start, end)

		// Clear grid
		grid.innerHTML = ''

		// Add items with fade-in animation
		currentItems.forEach((item, index) => {
			const delay = index * 100
			const element = document.createElement('div')
			element.className = 'portfolio-item'
			element.style.animationDelay = `${delay}ms`
			element.innerHTML = `
				<img src="${item.image_url}" alt="${item.title}">
				<div class="portfolio-item-overlay">
					<h3>${item.title}</h3>
					<p>${item.description}</p>
					<a href="${item.detail_url}" class="btn-view">Zobacz więcej</a>
				</div>
			`
			grid.appendChild(element)
		})

		// Update pagination if needed
		updatePagination(totalPages)
	}

	function updatePagination(totalPages) {
		const paginationContainer = document.querySelector('.portfolio-pagination')
		if (!paginationContainer) return

		if (totalPages <= 1) {
			paginationContainer.style.display = 'none'
			return
		}

		paginationContainer.style.display = 'flex'
		paginationContainer.innerHTML = ''

		for (let i = 1; i <= totalPages; i++) {
			const button = document.createElement('button')
			button.textContent = i
			button.className = i === currentPage ? 'active' : ''
			button.addEventListener('click', () => {
				currentPage = i
				updateDisplay(getCurrentCategory())
				window.scrollTo({
					top: grid.offsetTop - 100,
					behavior: 'smooth',
				})
			})
			paginationContainer.appendChild(button)
		}
	}

	function getCurrentCategory() {
		const activeBtn = document.querySelector('.portfolio-filter button.active')
		return activeBtn ? activeBtn.dataset.category : 'all'
	}

	// Filter button click handlers
	filterBtns.forEach(btn => {
		btn.addEventListener('click', () => {
			filterBtns.forEach(b => b.classList.remove('active'))
			btn.classList.add('active')
			currentPage = 1
			updateDisplay(btn.dataset.category)
		})
	})
})
