#!/bin/bash

# Laravel 서버 환경 자동 설정 스크립트
PROJECT_NAME="cosrsvp-aws"
APP_URL="https://cosrsvp-aws.hk-test.co.kr"

echo "🚀 Laravel 서버 환경 설정 시작: $PROJECT_NAME"
echo ""

# 1. 현재 디렉토리 확인
if [ ! -f "composer.json" ] || [ ! -f "artisan" ]; then
    echo "❌ 현재 디렉토리가 Laravel 프로젝트가 아닙니다."
    echo "프로젝트 루트 디렉토리에서 실행해주세요."
    exit 1
fi

# 2. .env 파일 생성 및 설정
echo "⚙️ 환경 설정 중..."
if [ ! -f ".env" ]; then
    echo "📄 .env 파일 생성 중..."
    cp .env.example .env
fi

# .env 파일 업데이트 (서버 환경)
echo "📝 .env 파일 설정 중..."
sed -i "s/APP_NAME=Laravel/APP_NAME=$PROJECT_NAME/" .env
sed -i "s|APP_URL=.*|APP_URL=$APP_URL|" .env
sed -i "s/APP_ENV=local/APP_ENV=production/" .env
sed -i "s/APP_DEBUG=true/APP_DEBUG=false/" .env

# DB 설정 (기본값, .env에 이미 설정되어 있으면 변경하지 않음)
if ! grep -q "^DB_HOST=" .env || grep -q "^DB_HOST=mysql" .env; then
    sed -i "s/DB_HOST=mysql/DB_HOST=127.0.0.1/" .env
fi

if ! grep -q "^DB_DATABASE=" .env || grep -q "^DB_DATABASE=laravel" .env; then
    sed -i "s/DB_DATABASE=laravel/DB_DATABASE=$PROJECT_NAME/" .env
fi

if ! grep -q "^DB_USERNAME=" .env || grep -q "^DB_USERNAME=sail" .env; then
    sed -i "s/DB_USERNAME=sail/DB_USERNAME=$PROJECT_NAME/" .env
fi

if ! grep -q "^DB_PASSWORD=" .env || grep -q "^DB_PASSWORD=password" .env; then
    sed -i "s/DB_PASSWORD=password/DB_PASSWORD=cosrsvp-aws@1234/" .env
fi

# 3. 권한 설정
echo "🔐 권한 설정 중..."
chmod -R 775 storage 2>/dev/null || echo "⚠️ storage 권한 설정 실패 (이미 설정되어 있을 수 있음)"
chmod -R 775 bootstrap/cache 2>/dev/null || echo "⚠️ bootstrap/cache 권한 설정 실패 (이미 설정되어 있을 수 있음)"

# storage 디렉토리 내 파일 권한 설정
find storage -type d -exec chmod 775 {} \; 2>/dev/null
find storage -type f -exec chmod 664 {} \; 2>/dev/null
find bootstrap/cache -type d -exec chmod 775 {} \; 2>/dev/null
find bootstrap/cache -type f -exec chmod 664 {} \; 2>/dev/null

echo "✅ 권한 설정 완료"

# 4. Composer 의존성 설치
echo "📦 Composer 의존성 설치 중..."
if [ ! -d "vendor" ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
else
    echo "✅ Composer 의존성이 이미 설치되어 있습니다."
fi

# 5. Laravel 애플리케이션 키 생성
echo "🔑 Laravel 애플리케이션 키 생성 중..."
if ! grep -q "^APP_KEY=" .env || grep -q "^APP_KEY=$" .env; then
    php artisan key:generate --force
else
    echo "✅ 애플리케이션 키가 이미 설정되어 있습니다."
fi

# 6. 저장소 심볼릭 링크 설정
echo "🔗 파일 저장소 심볼릭 링크 설정 중..."
if [ -L "public/storage" ]; then
    echo "✅ 심볼릭 링크가 이미 존재합니다."
else
    php artisan storage:link
    echo "✅ 심볼릭 링크가 생성되었습니다."
fi

# 7. MySQL 연결 확인
echo ""
echo "🗄️ 데이터베이스 설정 시작..."
echo "⏳ MySQL 연결 확인 중..."

# .env에서 DB 정보 추출
DB_HOST=$(grep "^DB_HOST=" .env | cut -d'=' -f2 | tr -d '"' | tr -d "'")
DB_PORT=$(grep "^DB_PORT=" .env | cut -d'=' -f2 | tr -d '"' | tr -d "'" || echo "3306")
DB_DATABASE=$(grep "^DB_DATABASE=" .env | cut -d'=' -f2 | tr -d '"' | tr -d "'")
DB_USERNAME=$(grep "^DB_USERNAME=" .env | cut -d'=' -f2 | tr -d '"' | tr -d "'")
DB_PASSWORD=$(grep "^DB_PASSWORD=" .env | cut -d'=' -f2 | tr -d '"' | tr -d "'")

# DB_HOST가 "mysql"이면 localhost로 변경 (Docker 컨테이너 이름)
if [ "$DB_HOST" = "mysql" ]; then
    DB_HOST="127.0.0.1"
    sed -i "s/DB_HOST=mysql/DB_HOST=127.0.0.1/" .env
fi

MAX_ATTEMPTS=10
ATTEMPT=0

while [ $ATTEMPT -lt $MAX_ATTEMPTS ]; do
    if mysqladmin ping -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" > /dev/null 2>&1; then
        echo "✅ MySQL 연결 성공!"
        break
    else
        ATTEMPT=$((ATTEMPT + 1))
        echo "⏳ MySQL 준비 중... ($ATTEMPT/$MAX_ATTEMPTS)"
        sleep 2
    fi
done

if [ $ATTEMPT -eq $MAX_ATTEMPTS ]; then
    echo "❌ MySQL 연결 실패. 데이터베이스 설정을 확인해주세요."
    echo "   DB_HOST: $DB_HOST"
    echo "   DB_DATABASE: $DB_DATABASE"
    echo "   DB_USERNAME: $DB_USERNAME"
    exit 1
fi

# 8. 마이그레이션 실행
echo "🗄️ 기본 마이그레이션 실행 중..."
php artisan migrate --force

# 9. 시더 실행 (기본 데이터 생성)
echo "🌱 시더 실행 중..."
php artisan db:seed

# 10. 세션 테이블 확인 및 생성
echo "📋 세션 테이블 확인 중..."
if ! php artisan tinker --execute="Schema::hasTable('sessions')" 2>/dev/null | grep -q "true"; then
    echo "📋 세션 테이블 생성 중..."
    
    php artisan tinker --execute="
        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (\$table) {
                \$table->string('id')->primary();
                \$table->foreignId('user_id')->nullable()->index();
                \$table->string('ip_address', 45)->nullable();
                \$table->text('user_agent')->nullable();
                \$table->text('payload');
                \$table->integer('last_activity')->index();
            });
            echo 'Sessions table created successfully';
        } else {
            echo 'Sessions table already exists';
        }
    "
else
    echo "✅ 세션 테이블이 이미 존재합니다."
fi

# 11. 캐시 정리
echo "🧹 캐시 정리 중..."
php artisan config:clear
php artisan view:clear

# 캐시 테이블이 있을 때만 캐시 클리어 실행
if php artisan tinker --execute="Schema::hasTable('cache')" 2>/dev/null | grep -q "true"; then
    php artisan cache:clear
else
    echo "⚠️ 캐시 테이블이 없어서 캐시 클리어를 건너뜁니다."
fi

echo ""
echo "=========================================="
echo "✅ 서버 환경 설정 완료!"
echo "=========================================="
echo ""
echo "📁 프로젝트 위치: $(pwd)"
echo "🌐 접속 URL: $APP_URL"
echo "🔧 관리 명령어: php artisan"
echo "🗄️ 데이터베이스: $DB_DATABASE"
echo ""
echo "🔑 기본 관리자 계정:"
echo "   이메일: admin@example.com"
echo "   비밀번호: password"
echo ""
echo "📊 생성된 주요 테이블:"
echo "   - users (사용자 관리)"
echo "   - admin_menus (관리자 메뉴)"
echo "   - user_menu_permissions (사용자 메뉴 권한)"
echo "   - settings (사이트 설정)"
echo "   - board_skins (게시판 스킨)"
echo "   - boards (게시판 관리)"
echo "   - board_posts (게시글)"
echo "   - board_comments (댓글)"
echo "   - board_settings (게시판 설정)"
echo "   - board_notices (공지사항)"
echo "   - board_gallerys (갤러리)"
echo ""
echo "🎉 백오피스 시스템이 준비되었습니다!"
echo ""
