import '../css/asset.css'
import FormChunkedFileField from './components/FormChunkedFileField.vue'
import DetailChunkedFileField from './components/DetailChunkedFileField.vue'
import IndexChunkedFileField from './components/IndexChunkedFileField.vue'

Nova.booting((app) => {
  app.component('form-chunked-file-field', FormChunkedFileField)
  app.component('detail-chunked-file-field', DetailChunkedFileField)
  app.component('index-chunked-file-field', IndexChunkedFileField)
})
