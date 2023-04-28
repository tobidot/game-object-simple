import IndexField from './components/IndexField'
import DetailField from './components/DetailField'
import FormField from './components/FormField'

Nova.booting((app, store) => {
    // (and disabling it conditionally with a process.env.NODE_ENV === 'production'
    // Vue.config.devtools = true;
    app.component('index-lookup-enum', IndexField)
    app.component('detail-lookup-enum', DetailField)
    app.component('form-lookup-enum', FormField)
})
