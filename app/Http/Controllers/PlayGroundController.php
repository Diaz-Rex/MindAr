<?php

namespace App\Http\Controllers;

use App\Models\Playground;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlayGroundController extends Controller
{
    public function lobby(Request $request)
    {
        if ($request->type === "downloadQr") {
            $validated = $request->validate([
                'qr_token' => ['required', 'string', 'size:26'],
            ]);

            $playground = Playground::where('user_id', auth()->id())
                ->where('qr_token', $validated['qr_token'])
                ->firstOrFail();

            $matrix = Builder::create()
                ->writer(new SvgWriter())
                ->data(route('mind-ar.playground.viewer', $playground))
                ->size(900)
                ->margin(36)
                ->build()
                ->getMatrix();

            $size = $matrix->getOuterSize();
            $blockSize = $matrix->getBlockSize();
            $margin = $matrix->getMarginLeft();
            $blockCount = $matrix->getBlockCount();
            $pixels = '';

            for ($y = 0; $y < $size; $y++) {
                $pixels .= "\x00";
                $row = (int) floor(($y - $margin) / $blockSize);

                for ($x = 0; $x < $size; $x++) {
                    $column = (int) floor(($x - $margin) / $blockSize);
                    $isBlack = $row >= 0 && $row < $blockCount &&
                        $column >= 0 && $column < $blockCount &&
                        $matrix->getBlockValue($row, $column) === 1;
                    $pixels .= $isBlack ? "\x00" : "\xFF";
                }
            }

            $header = pack('NNCCCCC', $size, $size, 8, 0, 0, 0, 0);
            $png = "\x89PNG\r\n\x1A\n";
            $png .= pack('N', strlen($header)) . 'IHDR' . $header . pack('N', crc32('IHDR' . $header));
            $compressed = gzcompress($pixels, 9);
            $png .= pack('N', strlen($compressed)) . 'IDAT' . $compressed . pack('N', crc32('IDAT' . $compressed));
            $png .= pack('N', 0) . 'IEND' . pack('N', crc32('IEND'));

            $fileName = Str::slug($playground->name);

            if ($fileName === '') {
                $fileName = 'playground';
            }

            return response($png, 200, [
                'Content-Type' => 'image/png',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '-qr.png"',
            ]);
        }

        if ($request->type === "store") {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:100'],
            ]);

            $playground = $request->user()->playgrounds()->create([
                'name' => $validated['name'],
                'qr_token' => (string) Str::ulid(),
            ]);

            return redirect()
                ->route('mind-ar.playground.build', $playground)
                ->with('success', 'Playground created.');
        }

        $playgrounds = Playground::where('user_id', auth()->id())
            ->latest()
            ->get();

        $playgrounds->each(function (Playground $playground) {
            $playground->qr_code = Builder::create()
                ->writer(new SvgWriter())
                ->data(route('mind-ar.playground.viewer', $playground))
                ->size(280)
                ->margin(12)
                ->build()
                ->getDataUri();
        });

        return view('playground.lobby', compact('playgrounds'));
    }

    public function index(Playground $playground)
    {
        if ($playground->user_id !== auth()->id()) {
            abort(403);
        }

        $modelLibrary = collect(glob(public_path('gltf/*.{gltf,glb}'), GLOB_BRACE))
            ->sort()
            ->values()
            ->map(fn (string $path) => [
                'key' => basename($path),
                'label' => Str::headline(pathinfo($path, PATHINFO_FILENAME)),
                'url' => asset('gltf/'.basename($path)),
            ]);

        return view('playground.build', compact('modelLibrary', 'playground'));
    }
}
