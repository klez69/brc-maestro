class VisitorTracker {
	constructor() {
		// Get base URL dynamically
		const scriptPath = document.currentScript ? document.currentScript.src : ''
		const baseUrl = scriptPath.substring(0, scriptPath.lastIndexOf('/js/'))
		this.apiEndpoint = `${baseUrl}/visitors_api.php?action=update`

		console.log('VisitorTracker initialized with endpoint:', this.apiEndpoint)

		// Check if tracking is allowed
		if (this.isTrackingAllowed()) {
			this.trackVisit()
		}

		// Set up interval to update tracking data periodically
		setInterval(() => {
			if (this.isTrackingAllowed()) {
				this.trackVisit()
			}
		}, 120000) // Update every 2 minutes
	}

	isTrackingAllowed() {
		// Check for Do Not Track setting
		if (window.doNotTrack || navigator.doNotTrack || navigator.msDoNotTrack) {
			if (
				window.doNotTrack === '1' ||
				navigator.doNotTrack === 'yes' ||
				navigator.doNotTrack === '1' ||
				navigator.msDoNotTrack === '1'
			) {
				return false
			}
		}

		// Check for stored consent
		const trackingConsent = localStorage.getItem('tracking_consent')
		if (trackingConsent === null) {
			// If no consent stored, ask for it
			this.showConsentBanner()
			return false
		}

		return trackingConsent === 'accepted'
	}

	showConsentBanner() {
		const banner = document.createElement('div')
		banner.className = 'tracking-consent-banner fixed bottom-0 left-0 right-0 bg-white shadow-lg p-4 z-50'
		banner.innerHTML = `
			<div class="container mx-auto flex flex-col md:flex-row items-center justify-between">
				<div class="mb-4 md:mb-0">
					<p class="text-sm text-gray-700">
						Ta strona używa podstawowych narzędzi analitycznych do zbierania anonimowych informacji o ruchu na stronie.
						Dane są przechowywane lokalnie i nie są udostępniane osobom trzecim.
					</p>
				</div>
				<div class="flex gap-2">
					<button id="accept-tracking" class="px-4 py-2 bg-brown text-white rounded hover:bg-opacity-90">
						Akceptuj
					</button>
					<button id="reject-tracking" class="px-4 py-2 border border-brown text-brown rounded hover:bg-gray-100">
						Odrzuć
					</button>
				</div>
			</div>
		`
		document.body.appendChild(banner)

		// Handle consent choices
		document.getElementById('accept-tracking').addEventListener('click', () => {
			localStorage.setItem('tracking_consent', 'accepted')
			banner.remove()
			this.trackVisit()
		})

		document.getElementById('reject-tracking').addEventListener('click', () => {
			localStorage.setItem('tracking_consent', 'rejected')
			banner.remove()
		})
	}

	getCurrentUser() {
		// Check various sources to identify the current user
		const user = {
			isLoggedIn: false,
			username: null,
			role: null,
			userId: null,
		}

		// Check PHP session via cookie
		if (document.cookie.includes('PHPSESSID')) {
			user.isLoggedIn = true
		}

		// Check adminLoggedIn flag in localStorage or sessionStorage
		if (localStorage.getItem('adminLoggedIn') === 'true' || sessionStorage.getItem('adminLoggedIn') === 'true') {
			user.isLoggedIn = true
			user.role = 'admin'
			user.username = 'Administrator' // Default admin username
		}

		// Check for admin_token cookie
		const adminTokenMatch = document.cookie.match(/admin_token=([^;]+)/)
		if (adminTokenMatch && adminTokenMatch[1]) {
			user.isLoggedIn = true
			user.role = 'admin'
			user.token = adminTokenMatch[1]
			user.username = 'Administrator' // Default admin username
		}

		// Check for username in window object
		if (typeof window.username !== 'undefined') {
			user.username = window.username
		} else if (typeof window.userInfo !== 'undefined' && window.userInfo.username) {
			user.username = window.userInfo.username
			user.role = window.userInfo.role || user.role
			user.userId = window.userInfo.userId || user.userId
			user.isLoggedIn = true
		}

		// Try to get any user data from localStorage as well
		try {
			const userData = localStorage.getItem('userData')
			if (userData) {
				const parsedUserData = JSON.parse(userData)
				if (parsedUserData && parsedUserData.username) {
					user.username = parsedUserData.username
					user.role = parsedUserData.role || user.role
					user.userId = parsedUserData.id || user.userId
					user.isLoggedIn = true
				}
			}
		} catch (e) {
			console.error('Error getting user data from localStorage:', e)
		}

		return user
	}

	async trackVisit() {
		try {
			// Get current user information
			const userInfo = this.getCurrentUser()

			// Only collect essential information
			const visitData = {
				page: window.location.pathname,
				page_url: window.location.pathname,
				referrer: document.referrer || 'direct',
				user_agent: navigator.userAgent,
				screen_resolution: `${window.screen.width}x${window.screen.height}`,
				language: navigator.language,
				visit_timestamp: new Date().toISOString(),
				user_info: userInfo, // Add user information
			}

			console.log('Tracking visit data:', visitData)

			const response = await fetch(this.apiEndpoint, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify(visitData),
				// Include credentials to get session information
				credentials: 'same-origin',
			})

			if (!response.ok) {
				const errorText = await response.text()
				try {
					const errorData = JSON.parse(errorText)
					throw new Error(errorData.message || 'Failed to track visit')
				} catch (jsonError) {
					throw new Error(`Failed to track visit: ${errorText}`)
				}
			}

			const result = await response.json()
			console.log('Visit tracked successfully:', result)
		} catch (error) {
			console.error('Error tracking visit:', error)
			// Don't throw the error - just log it to avoid breaking the page
		}
	}
}

// Initialize tracker when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
	new VisitorTracker()
})
