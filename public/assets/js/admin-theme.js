const { createApp } = Vue;

createApp({
    data() {
        return {
            isDark: false
        }
    },
    methods: {
        toggleTheme() {
            this.isDark = !this.isDark;
            if (this.isDark) {
                document.body.classList.add('dark-theme');
                document.querySelector('.navbar').classList.remove('navbar-light', 'bg-light');
                document.querySelector('.navbar').classList.add('navbar-dark', 'bg-dark');
                localStorage.setItem('theme', 'dark');
            } else {
                document.body.classList.remove('dark-theme');
                document.querySelector('.navbar').classList.remove('navbar-dark', 'bg-dark');
                document.querySelector('.navbar').classList.add('navbar-light', 'bg-light');
                localStorage.setItem('theme', 'light');
            }
        },
        loadTheme() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                this.isDark = true;
                document.body.classList.add('dark-theme');
                document.querySelector('.navbar').classList.remove('navbar-light', 'bg-light');
                document.querySelector('.navbar').classList.add('navbar-dark', 'bg-dark');
            }
        }
    },
    mounted() {
        this.loadTheme();
    }
}).mount('#app');