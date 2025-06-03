class AttributeStorage {
    constructor() {
        this.terms = {};
        this.lastUpdated = 0;
        this.expiration = 0;
        this.load();
    }
    
    load() {
        try {
            const storedData = localStorage.getItem('attributeTerms');
            const timestamp = localStorage.getItem('attributeTermsTimestamp');
            const expiration = localStorage.getItem('attributeTermsExpiration');
            
            if (storedData && timestamp && expiration) {
                this.terms = JSON.parse(storedData);
                this.lastUpdated = parseInt(timestamp);
                this.expiration = parseInt(expiration);
                
                // Check if data is expired
                const currentTime = Date.now();
                if (currentTime - this.lastUpdated > this.expiration) {
                    console.log('Attribute terms expired, refreshing...');
                    this.terms = {};
                    this.refreshFromServer();
                }
            } else {
                this.refreshFromServer();
            }
        } catch (e) {
            console.error('Error loading attribute terms from localStorage:', e);
            this.refreshFromServer();
        }
    }
    
    refreshFromServer() {
        console.log('Refreshing attribute terms from server...');
        $.ajax({
            url: 'refresh_attribute_terms.php',
            method: 'GET',
            dataType: 'json',
            success: (data) => {
                this.terms = data;
                this.lastUpdated = Date.now();
                localStorage.setItem('attributeTerms', JSON.stringify(data));
                localStorage.setItem('attributeTermsTimestamp', this.lastUpdated);
                localStorage.setItem('attributeTermsExpiration', 3600000); // 1 hour
            },
            error: () => {
                console.error('Failed to refresh attribute terms');
            }
        });
    }
    
    getTerms(taxonomy) {
        return this.terms[taxonomy] || {};
    }
    
    search(taxonomy, query) {
        const terms = this.getTerms(taxonomy);
        const results = [];
        const queryLower = query.toLowerCase();
        
        for (const [id, name] of Object.entries(terms)) {
            if (name.toLowerCase().includes(queryLower)) {
                results.push({
                    id: id,
                    name: name
                });
                
                // Limit results to 15
                if (results.length >= 15) break;
            }
        }
        
        return results;
    }
}

// Create a global instance
const attributeStorage = new AttributeStorage();


