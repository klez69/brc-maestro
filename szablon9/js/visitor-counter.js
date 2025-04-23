/**
 * Visitor Counter Module
 * Handles fetching and displaying the number of active visitors on the site
 */
class VisitorCounter {
	constructor(options = {}) {
		this.counterId = options.counterId || 'active-visitors-count'
		this.counterElement = document.getElementById(this.counterId)
		this.containerElement = document.getElementById('visitor-counter')
		this.apiEndpoint = options.apiEndpoint || 'visitors_api.php?action=get'
		this.updateInterval = options.updateInterval || 30000 // 30 seconds
		this.minVisitors = options.minVisitors || 1 // Minimum number to show
		this.animationSpeed = options.animationSpeed || 300

		// Initialize
		this.init()
	}

	init() {
		// Skip initialization if elements don't exist
		if (!this.counterElement || !this.containerElement) {
			console.warn('Visitor counter elements not found')
			return
		}

		// Initial fetch
		this.fetchActiveVisitors()

		// Set up interval for updates
		setInterval(() => {
			this.fetchActiveVisitors()
		}, this.updateInterval)

		// Add hover effect to container
		this.containerElement.addEventListener('mouseenter', () => {
			this.counterElement.style.transform = 'scale(1.2)'
		})

		this.containerElement.addEventListener('mouseleave', () => {
			this.counterElement.style.transform = 'scale(1)'
		})
	}

	async fetchActiveVisitors() {
		try {
			const response = await fetch(this.apiEndpoint)

			if (!response.ok) {
				throw new Error('Failed to fetch visitor data')
			}

			const data = await response.json()

			if (data.status === 'success' && Array.isArray(data.visitors)) {
				// Ensure we show at least the minimum number of visitors
				let visitorCount = Math.max(data.visitors.length, this.minVisitors)
				this.updateCounter(visitorCount)
			}
		} catch (error) {
			console.error('Error fetching visitor count:', error)
			// Show minimum visitor count on error
			this.updateCounter(this.minVisitors)
		}
	}

	updateCounter(count) {
		// Don't update if the value is the same
		if (this.counterElement.textContent === count.toString()) {
			return
		}

		// Animate the counter change
		this.animateCounterChange(count)
	}

	animateCounterChange(newValue) {
		const currentValue = parseInt(this.counterElement.textContent, 10) || 0
		const diff = newValue - currentValue

		if (diff === 0) return

		// Highlight effect
		this.counterElement.style.transition = `color ${this.animationSpeed}ms`
		this.counterElement.style.color = diff > 0 ? '#4CAF50' : '#F44336'

		// Set the new value
		this.counterElement.textContent = newValue

		// Reset color after animation
		setTimeout(() => {
			this.counterElement.style.color = ''
		}, this.animationSpeed)
	}
}

// Initialize the counter when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
	new VisitorCounter()
})
