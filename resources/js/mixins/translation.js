// resources/js/mixins/translation.js
export default {
    methods: {
        __(key, replacements = {}) {
            let translation = key.split('.').reduce((t, i) => t?.[i], this.$page.props.translations) || key;
            
            Object.keys(replacements).forEach(r => {
                translation = translation.replace(`:${r}`, replacements[r]);
            });
            
            return translation;
        }
    }
}