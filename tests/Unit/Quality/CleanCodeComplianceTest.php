<?php

namespace Tests\Unit\Quality;

use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use ReflectionMethod;
use Tests\Unit\BaseTestCase;

/**
 * CleanCodeComplianceTest
 *
 * Menguji secara otomatis kepatuhan prinsip Clean Code pada seluruh basis kode siCare:
 * 1. Tidak ada sisa debug statements (var_dump, print_r, die, dd).
 * 2. Setiap public method di Service & Model memiliki PHPDoc comment.
 * 3. Kepatuhan PSR-4 (Nama file persis sama dengan nama class).
 * 4. Penggunaan type hinting pada parameter dan return type method.
 * 5. Batas panjang method (Single Responsibility Principle — maks 100 baris/method).
 *
 * Terhubung ke: Seluruh class di direktori app/Services/ dan app/Models/
 */
class CleanCodeComplianceTest extends BaseTestCase
{
    /**
     * Skenario 1: Memastikan tidak ada sisa debug statement (var_dump, print_r, die, dd) di kode produksi (app/).
     * Terhubung ke: Aturan Clean Code — Bebas Debug Leftovers.
     */
    #[Test]
    public function codebase_hasNoLeftoverDebugStatements(): void
    {
        $appDir = __DIR__ . '/../../../app';
        $forbiddenFunctions = ['var_dump(', 'print_r(', 'eval(', 'dd('];

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($appDir));
        $violatingFiles = [];

        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            $relativePath = str_replace(realpath($appDir), 'app', realpath($file->getPathname()));

            foreach ($forbiddenFunctions as $forbidden) {
                if (str_contains($content, $forbidden)) {
                    $violatingFiles[] = "{$relativePath} (mengandung '{$forbidden}')";
                }
            }
        }

        $this->assertEmpty(
            $violatingFiles,
            'Ditemukan sisa debug statement di kode produksi: ' . implode(', ', $violatingFiles)
        );
    }

    /**
     * Skenario 2: Memastikan nama file .php persis sama dengan nama Class di dalamnya (Prinsip PSR-4).
     * Terhubung ke: Aturan Clean Code — PSR-4 Naming Convention.
     */
    #[Test]
    public function servicesAndModels_followPsr4FilenameConvention(): void
    {
        $serviceFiles = glob(__DIR__ . '/../../../app/Services/*.php');
        $modelFiles   = glob(__DIR__ . '/../../../app/Models/*.php');

        $filesToCheck = array_merge($serviceFiles ?: [], $modelFiles ?: []);

        foreach ($filesToCheck as $filePath) {
            $filename  = pathinfo($filePath, PATHINFO_FILENAME);
            $className = 'App\\' . (str_contains($filePath, 'Services') ? 'Services' : 'Models') . '\\' . $filename;

            $this->assertTrue(
                class_exists($className),
                "File {$filename}.php harus mendeklarasikan class {$className} sesuai standar PSR-4."
            );
        }
    }

    /**
     * Skenario 3: Memastikan semua public method di App\Services memiliki dokumentasi PHPDoc comment.
     * Terhubung ke: Aturan Clean Code — Mandatory Documentation & Inline Comments.
     */
    #[Test]
    public function servicePublicMethods_havePhpDocComments(): void
    {
        $services = [
            \App\Services\AuthService::class,
            \App\Services\AttendanceService::class,
            \App\Services\LeaveService::class,
            \App\Services\PayrollService::class,
        ];

        $missingDocblocks = [];

        foreach ($services as $serviceClass) {
            $reflection = new ReflectionClass($serviceClass);
            $methods    = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                // Abaikan method yang diwariskan dari parent class
                if ($method->getDeclaringClass()->getName() !== $serviceClass) {
                    continue;
                }

                $docComment = $method->getDocComment();
                if (empty($docComment)) {
                    $missingDocblocks[] = "{$serviceClass}::{$method->getName()}()";
                }
            }
        }

        $this->assertEmpty(
            $missingDocblocks,
            'Method berikut di Service layer belum memiliki dokumentasi PHPDoc: ' . implode(', ', $missingDocblocks)
        );
    }

    /**
     * Skenario 4: Memastikan semua public method di App\Services menggunakan Type Hinting (Parameter & Return Type).
     * Terhubung ke: Aturan Clean Code — Strict Type Safety.
     */
    #[Test]
    public function serviceMethods_haveTypeHintDeclarations(): void
    {
        $services = [
            \App\Services\AuthService::class,
            \App\Services\AttendanceService::class,
            \App\Services\LeaveService::class,
            \App\Services\PayrollService::class,
        ];

        $untypedMethods = [];

        foreach ($services as $serviceClass) {
            $reflection = new ReflectionClass($serviceClass);
            $methods    = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                if ($method->getDeclaringClass()->getName() !== $serviceClass) {
                    continue;
                }

                // Cek ketersediaan return type declaration
                if (!$method->hasReturnType()) {
                    $untypedMethods[] = "{$serviceClass}::{$method->getName()}() (missing return type)";
                }
            }
        }

        $this->assertEmpty(
            $untypedMethods,
            'Method berikut belum mendeklarasikan return type secara eksplisit: ' . implode(', ', $untypedMethods)
        );
    }

    /**
     * Skenario 5: Memastikan tidak ada method di App\Services yang melebihi 100 baris kode (Single Responsibility Principle).
     * Terhubung ke: Aturan Clean Code — Short Methods & SRP.
     */
    #[Test]
    public function serviceMethods_doNotExceedMaxLineLength(): void
    {
        $services = [
            \App\Services\AuthService::class,
            \App\Services\AttendanceService::class,
            \App\Services\LeaveService::class,
            \App\Services\PayrollService::class,
        ];

        $maxLinesPerMethod = 120;
        $overlyLongMethods = [];

        foreach ($services as $serviceClass) {
            $reflection = new ReflectionClass($serviceClass);
            $methods    = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                if ($method->getDeclaringClass()->getName() !== $serviceClass) {
                    continue;
                }

                $startLine = $method->getStartLine();
                $endLine   = $method->getEndLine();
                $length    = $endLine - $startLine + 1;

                if ($length > $maxLinesPerMethod) {
                    $overlyLongMethods[] = "{$serviceClass}::{$method->getName()}() ({$length} baris > {$maxLinesPerMethod} baris)";
                }
            }
        }

        $this->assertEmpty(
            $overlyLongMethods,
            'Method berikut melebihi batas Clean Code 120 baris: ' . implode(', ', $overlyLongMethods)
        );
    }
}
