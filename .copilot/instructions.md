# Central Storage - Copilot Instructions

## Project Overview
Central Storage is a Laravel-based centralized file storage engine. It handles file uploads with
deduplication, on-the-fly image resizing, and pluggable file processors (video transcoding, archive
extraction, image effects). Multiple client applications ("consumers") authenticate via HMAC-signed
API requests.

## Tech Stack
- **PHP 8.1** with **Laravel 8**
- **MySQL 8.0** database
- **Apache 2.4** web server
- **Imagick / GD** for image processing
- **AWS SDK** for S3 storage and MediaConvert video transcoding
- **Docker Compose** for local development (webserver on port 8095, mysql on port 3313)

## Architecture

### Key Models & Relationships
- **Asset**: An uploaded file (deduplicated by SHA256 hash). Has many `Variation`s and `ConsumerAsset`s.
- **Consumer**: An API client application with key + secret. Has many `Processor`s and `ConsumerAsset`s.
- **ConsumerAsset**: Links a Consumer to an Asset (with optional expiry). Identified by `ca_key`.
- **Processor**: A file transformation handler (e.g. video transcoding). Has `ProcessorTrigger`s (MIME filters), `ProcessorConfig`s, and `ProcessorJob`s.
- **ProcessorJob**: Tracks processor execution. States: PREPARED → PENDING → FINISHED/FAILED.
- **ProcessorTrigger**: MIME type regex filter that determines when a Processor is triggered.
- **Variation**: A transformed version of an Asset, produced by a Processor.

### Upload Flow (POST /api/v1/upload)
1. `ApiAuthentication` middleware validates consumer key + HMAC signature
2. `UploadController::upload()` validates file and checks image size limit
3. `AssetUploader` checks for duplicates (hash-based), uploads if new
4. `ConsumerAsset` record created linking consumer to asset
5. Matching `Processor`s are triggered based on MIME type
6. Returns JSON with asset keys and metadata

### Asset Serving (GET /assets/{id})
1. `AssetController` looks up `ConsumerAsset` by key
2. Checks expiry, loads asset and requested variation
3. Supports on-the-fly image resizing (cached)
4. Returns file with appropriate cache headers

### Available Processors
- **AwsMediaConvert**: Video transcoding via AWS (requires S3)
- **ExtractArchive**: ZIP file extraction
- **GreenScreen**: Image background replacement using Imagick

## File Structure
```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/UploadController.php    # Upload API endpoint
│   │   ├── AssetController.php          # Asset serving & resize
│   │   ├── CachedProxyController.php    # External URL caching proxy
│   │   └── ProcessorController.php      # Processor management UI
│   └── Middleware/
│       └── ApiAuthentication.php        # Consumer HMAC auth
├── Models/
│   ├── Asset.php                        # Uploaded file model
│   ├── Consumer.php                     # API client model
│   ├── ConsumerAsset.php                # Consumer-Asset link
│   ├── Processor.php                    # Base processor logic
│   ├── ProcessorJob.php                 # Job tracking
│   ├── ProcessorTrigger.php             # MIME type filter
│   └── Variation.php                    # Transformed file output
├── Processors/
│   ├── AwsMediaConvert.php              # Video transcoding
│   ├── ExtractArchive.php               # Archive extraction
│   └── GreenScreen.php                  # Image background removal
└── Console/Commands/                    # Artisan commands
config/
├── assets.php                           # Asset storage & upload limits
├── filesystems.php                      # Disk configuration
└── image.php                            # Image driver selection
routes/
├── api.php                              # API routes (upload, delete)
├── public.php                           # Public asset serving routes
└── web.php                              # Web UI routes
```

## Configuration

### Key Environment Variables
| Variable | Default | Description |
|----------|---------|-------------|
| `ASSETS_DISK` | `local` | Storage disk (`local` or `s3`) |
| `MAX_IMAGE_FILE_SIZE` | `20971520` (20MB) | Max upload size for image files (bytes) |
| `INTERVENTION_DRIVER` | `imagick` | Image processing driver (`imagick` or `gd`) |
| `AWS_KEY` | | AWS access key (required for S3/MediaConvert) |
| `AWS_SECRET` | | AWS secret key |
| `AWS_REGION` | | AWS region |
| `AWS_BUCKET` | | S3 bucket name |
| `AWS_CLOUDFRONT` | | CloudFront distribution URL |

### Upload Limits
- **Images** (`image/*`): Limited by `MAX_IMAGE_FILE_SIZE` (default 20MB). Server-side MIME detection is used.
- **Videos** (`video/*`): No application-level size limit (only PHP `upload_max_filesize` applies).
- **Other files**: No application-level size limit.
- **PHP-level limit**: `upload_max_filesize` and `post_max_size` set to 1GB in `docker/php/upload.ini`.

## Development

### Running Locally
```bash
docker-compose up
php artisan migrate
php artisan user:create
```

### Running Tests
```bash
php vendor/bin/phpunit
```

### Key Commands
- `php artisan user:create` - Create admin user
- `php artisan processor:process {id}` - Run a processor batch
- `php artisan processor:update` - Update pending processor jobs
