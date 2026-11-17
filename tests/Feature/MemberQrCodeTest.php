<?php

namespace Tests\Feature;

use App\Models\Member;
use Tests\TestCase;

/**
 * The member QR card must render without the libxml (XMLWriter) extension,
 * which the production server lacks.
 */
class MemberQrCodeTest extends TestCase
{
    public function test_qr_svg_matches_the_qr_matrix(): void
    {
        $svg = qrCodeSvg(192, 799);

        $this->assertStringStartsWith('<svg', $svg);
        $this->assertStringContainsString('width="799"', $svg);

        $matrix = \BaconQrCode\Encoder\Encoder::encode('192', \BaconQrCode\Common\ErrorCorrectionLevel::L())->getMatrix();
        $this->assertStringContainsString('viewBox="0 0 ' . $matrix->getWidth() . ' ' . $matrix->getWidth() . '"', $svg);

        // every dark module is drawn exactly once
        $dark = 0;
        foreach ($matrix->getArray() as $row) {
            foreach ($row as $cell) {
                $dark += $cell === 1 ? 1 : 0;
            }
        }
        preg_match_all('/h(\d+)v1/', $svg, $runs);
        $this->assertSame($dark, array_sum(array_map('intval', $runs[1])));
    }

    public function test_member_qr_card_renders(): void
    {
        $member = new Member();
        $member->id = 192;
        $member->name = 'Budi';
        $member->address = 'Jl. Kuncen';

        $html = view('layout.member.qr-code', compact('member'))->render();

        $this->assertStringContainsString('<svg', $html);
        $this->assertStringContainsString('Budi', $html);
    }

    public function test_member_qr_phone_download_page_renders(): void
    {
        $member = new Member();
        $member->id = 192;
        $member->name = 'Budi "Kuncen"';
        $member->address = 'Jl. Kuncen';

        $qr_rows = ['101', '010', '101'];
        $file_name = 'qr-member-192-budi-kuncen.png';

        $html = view('layout.member.qr-code-download', compact('member', 'qr_rows', 'file_name'))->render();

        $this->assertStringContainsString('width="1080" height="1920"', $html);
        $this->assertStringContainsString('["101","010","101"]', $html);
        $this->assertStringContainsString('qr-member-192-budi-kuncen.png', $html);
        // nama di-escape sebagai JSON sehingga kutip tidak merusak script
        $this->assertStringContainsString('name: ' . json_encode($member->name, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), $html);
    }
}
