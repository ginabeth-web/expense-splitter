import '@mdi/font/css/materialdesignicons.css';
import 'vuetify/styles';
import { createVuetify } from 'vuetify';

export default createVuetify({
    theme: {
        defaultTheme: 'light',
        themes: {
            light: {
                colors: {
                    primary: '#00796B',
                    secondary: '#455A64',
                    background: '#F5F7F8',
                    success: '#2E7D32',
                    error: '#C62828',
                },
            },
        },
    },
    defaults: {
        VTextField: { variant: 'outlined', density: 'comfortable' },
        VSelect: { variant: 'outlined', density: 'comfortable' },
        VCard: { rounded: 'lg' },
        VBtn: { rounded: 'lg' },
    },
});
