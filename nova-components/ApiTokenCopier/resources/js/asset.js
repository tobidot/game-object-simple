import '../css/asset.css'
import ApiTokenCopier from './components/ApiTokenCopier.vue'

Nova.booting(app => {
  app.component('api-token-copier', ApiTokenCopier)
})
