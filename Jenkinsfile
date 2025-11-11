pipeline {
    agent any

    parameters {
        booleanParam(
            name: 'DEPLOY',
            defaultValue: false,
            description: 'Deploy to production after successful build'
        )
    }

    environment {
        APP_NAME = 'valletta'
        COMPOSE_PROJECT_NAME = 'valletta'
        BACKEND_DIR = 'backend'
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Validate Configuration') {
            steps {
                dir(env.BACKEND_DIR) {
                    script {
                        echo "🔍 Validating Docker configuration for ${APP_NAME}"

                        // Try to load .env from Jenkins credentials if available
                        def hasEnvFile = false
                        try {
                            withCredentials([file(credentialsId: "valletta.env", variable: 'ENV_FILE')]) {
                                hasEnvFile = true
                            }
                        } catch (Exception e) {
                            echo "⚠️ No environment file credential found, using default .env if present"
                        }

                        if (hasEnvFile) {
                            withCredentials([file(credentialsId: "valletta.env", variable: 'ENV_FILE')]) {
                                sh '''
                                    cp "$ENV_FILE" .env
                                    chmod 600 .env
                                    docker compose config
                                '''
                            }
                        } else {
                            sh 'docker compose config'
                        }
                    }
                }
            }
        }

        stage('Build Docker Image') {
            steps {
                dir(env.BACKEND_DIR) {
                    script {
                        echo "🏗️ Building Docker image..."

                        sh '''
                            docker build \
                                --no-cache \
                                --pull \
                                -t ${APP_NAME}:${BUILD_NUMBER} \
                                -t ${APP_NAME}:latest .
                        '''
                    }
                }
            }
        }

        stage('Test Build') {
            steps {
                dir(env.BACKEND_DIR) {
                    script {
                        echo "🚀 Starting temporary test environment..."

                        def hasEnv = fileExists('.env')
                        def composeCmd = hasEnv ? 'docker compose --env-file .env' : 'docker compose'

                        sh """
                            ${composeCmd} up -d
                            echo "⏳ Waiting for services to initialize..."
                            sleep 30
                        """

                        // Health check
                        sh '''
                            echo "🔍 Checking Laravel app health..."
                            for i in 1 2 3 4 5; do
                                if curl -s -f http://localhost >/dev/null 2>&1 || \
                                   curl -s -f http://localhost/api/products >/dev/null 2>&1; then
                                    echo "✅ Laravel app is running"
                                    break
                                else
                                    echo "⏳ Attempt $i: Not ready yet, retrying..."
                                    sleep 10
                                fi
                            done
                        '''

                        // Run tests inside container


                        sh """
                            echo "📊 Active containers:"
                            ${composeCmd} ps
                            echo "🛑 Stopping temporary environment..."
                            ${composeCmd} down
                        """
                    }
                }
            }
        }

        stage('Deploy to Production') {
            when {
                expression { params.DEPLOY }
            }
            steps {
                dir(env.BACKEND_DIR) {
                    script {
                        echo "🚀 Deploying ${APP_NAME} to production..."

                        withCredentials([file(credentialsId: "valletta.env", variable: 'ENV_FILE')]) {
                            sh '''
                                cp "$ENV_FILE" .env
                                chmod 600 .env

                                echo "🛑 Stopping old containers..."
                                docker compose --env-file .env down --remove-orphans || true

                                echo "🏗️ Rebuilding and starting services..."
                                docker compose --env-file .env up -d --build

                                echo "⏳ Waiting for containers to stabilize..."
                                sleep 60
                            '''
                        }

                        sh '''
                            echo "🔍 Running production health check..."
                            for i in 1 2 3 4 5; do
                                if curl -s -f http://localhost >/dev/null 2>&1 || \
                                   curl -s -f http://localhost/api/products >/dev/null 2>&1; then
                                    echo "✅ Deployment successful and healthy"
                                    break
                                else
                                    echo "⏳ Attempt $i: Waiting..."
                                    sleep 20
                                fi
                            done

                            docker compose --env-file .env ps
                        '''
                    }
                }
            }
        }
    }

    post {
        always {
            script {
                echo "🧹 Cleaning up workspace..."
                dir(env.BACKEND_DIR) {
                    sh 'rm -f .env || true'
                }
                cleanWs()
            }
        }

        success {
            echo "✅ Pipeline ${env.BUILD_NUMBER} completed successfully"
            script {
                if (params.DEPLOY) {
                    echo "🚀 ${APP_NAME} deployed to production"
                }
            }
        }

        failure {
            echo "❌ Pipeline ${env.BUILD_NUMBER} failed"
            script {
                dir(env.BACKEND_DIR) {
                    sh '''
                        echo "=== Container Status ==="
                        docker compose ps 2>/dev/null || true
                        echo "=== Recent Logs ==="
                        docker compose logs --tail=50 2>/dev/null || true
                    '''
                }
            }
        }

        unstable {
            echo "⚠️ Pipeline ${env.BUILD_NUMBER} completed with warnings"
        }
    }
}
