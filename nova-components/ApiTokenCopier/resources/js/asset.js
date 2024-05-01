import ApiTokenCopier from './components/ApiTokenCopier'

Nova.booting(app => {
  app.component('api-token-copier', ApiTokenCopier)
})
