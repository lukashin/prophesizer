pipeline {
  agent any
  stages {
    stage('build') {
      steps {
        error 'oops'
        sh 'echo whoami'
      }
    }
  }
}