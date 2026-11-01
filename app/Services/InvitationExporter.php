<?php

namespace App\Services;

use App\Models\WeddingOrder;
use Carbon\Carbon;

class InvitationExporter
{
    private function person(array $data, string $gender): array
    {
        $name = trim($data['full_name'] ?? '');
        $short = trim($data['short_name'] ?? '') ?: $name;
        $role = $gender === 'female' ? 'Putri' : 'Putra';
        $sequence = trim($data['birth_order'] ?? '');
        $dad = trim($data['father'] ?? '');
        $mom = trim($data['mother'] ?? '');
        $orderPart = $sequence ? (ctype_digit($sequence) ? ' ke-'.$sequence : ' '.$sequence) : '';
        $parentParts = [];
        if ($dad !== '') $parentParts[] = 'Bapak '.$dad;
        if ($mom !== '') $parentParts[] = 'Ibu '.$mom;
        $parents = count($parentParts) > 0 ? $role.$orderPart.' dari '.implode(' & ', $parentParts) : '';
        return ['full_name' => $name, 'short_name' => $short, 'parents' => $parents];
    }

    public function data(WeddingOrder $order): array
    {
        $p = $order->payload ?? [];
        $female = $this->person($p['bride'] ?? [], 'female');
        $male = $this->person($p['groom'] ?? [], 'male');
        [$a, $b] = $order->display_order === 'male_first' ? [$male, $female] : [$female, $male];
        $events = [];
        foreach (array_slice($p['events'] ?? [], 0, 3) as $event) {
            if (!trim($event['name'] ?? '') && !trim($event['date'] ?? '')) continue;
            $events[] = array_filter([
                'name' => $event['name'] ?? '',
                'date' => $event['date'] ?? '',
                'time' => trim($event['time'] ?? '') ?: '.',
                'address' => $event['location'] ?? '',
                'maps' => $event['maps'] ?? '',
            ], fn($v) => $v !== '');
        }
        $out = ['order_number' => $order->order_number, 'theme' => null,
            'mempelai_1' => array_filter($a, fn($v) => $v !== ''),
            'mempelai_2' => array_filter($b, fn($v) => $v !== ''), 'events' => $events];
        if ($order->product_type === 'digital') {
            $gifts = [];
            foreach (array_slice($p['love_gift'] ?? [], 0, 3) as $gift) {
                if (!isset($gift['account_number']) || trim($gift['account_number']) === '') continue;
                $gift['bank'] = strcasecmp(trim($gift['bank'] ?? ''), 'seabank') === 0 ? 'Sea Bank' : ($gift['bank'] ?? '');
                $gifts[] = $gift;
            }
            if ($gifts) $out['lovegift'] = $gifts;
            if (!empty($p['gift_delivery']['address'])) $out['gift_delivery'] = $p['gift_delivery'];
        }
        return $out;
    }

    public function json(WeddingOrder $order): string
    {
        return json_encode($this->data($order), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    public function jsx(WeddingOrder $order): string
    {
        $p = $order->payload ?? [];
        $d = $this->data($order);
        $frame = [
            'mempelai_1' => $d['mempelai_1']['full_name'] ?? '',
            'mempelai_2' => $d['mempelai_2']['full_name'] ?? '',
            'pendek_1' => $d['mempelai_1']['short_name'] ?? '',
            'pendek_2' => $d['mempelai_2']['short_name'] ?? '',
            'ortumempelai_1' => $d['mempelai_1']['parents'] ?? '',
            'ortumempelai_2' => $d['mempelai_2']['parents'] ?? '',
            'Pengangkat_Hajat' => implode('\r', $p['pengangkat_hajat'] ?? []),
            'Turut_Mengundang' => empty($p['turut_mengundang']) ? '' : 'Turut Mengundang',
        ];
        foreach ([1,2,3] as $i) foreach (['acara','tanggal','waktu','Lokasi'] as $prefix) $frame[$prefix.'_'.$i] = '';
        $events = array_values(array_filter(array_slice($p['events'] ?? [], 0, 3), fn ($e) => !empty($e['name']) || !empty($e['date'])));
        // Historic Illustrator behavior: a single event uses slot 3.
        foreach ($events as $index => $event) {
            $slot = count($events) === 1 ? 3 : $index + 1;
            $frame['acara_'.$slot] = $event['name'] ?? '';
            $frame['tanggal_'.$slot] = empty($event['date']) ? '' : Carbon::parse($event['date'])->locale('id')->translatedFormat('l, d F Y');
            $frame['waktu_'.$slot] = $event['time'] ?? '';
            $frame['Lokasi_'.$slot] = $event['location'] ?? '';
        }
        $frame['tanggaldepan_2'] = $frame['tanggal_2'];
        $frame['tanggalbelakang_2'] = $frame['tanggal_2'];
        $js = json_encode($frame, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        return "// DRAF JSX — wajib diuji dengan template Adobe Illustrator asli sebelum produksi.\n".
            "#target illustrator\n(function () {\n".
            "  if (app.documents.length === 0) { alert('Buka dokumen Illustrator dahulu.'); return; }\n".
            "  var frames = ".$js.";\n  var doc = app.activeDocument;\n".
            "  for (var i = 0; i < doc.textFrames.length; i++) {\n".
            "    var tf = doc.textFrames[i];\n".
            "    if (Object.prototype.hasOwnProperty.call(frames, tf.name)) tf.contents = frames[tf.name];\n".
            "  }\n  alert('Data draf selesai diterapkan; periksa seluruh placeholder.');\n})();\n";
    }
}
