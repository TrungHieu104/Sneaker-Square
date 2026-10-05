<?php

namespace App\Exports;

use App\Exports\Sheets\ListingSheet;
use App\Models\UserModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ExportAccounts extends ListingSheet
{
    private function __construct(private readonly int $role, private readonly string $heading) {}

    public static function customers(): self
    {
        return new self(0, 'Danh sách khách hàng | Sneaker Square');
    }

    public static function admins(): self
    {
        return new self(1, 'Danh sách quản trị viên | Sneaker Square');
    }

    protected function heading(): string
    {
        return $this->heading;
    }

    protected function columns(): array
    {
        return ['Username', 'Họ tên', 'Email', 'Trạng thái', 'Ngày đăng ký'];
    }

    protected function records(): Collection
    {
        return UserModel::where('user_role', $this->role)->get();
    }

    protected function row(mixed $user): array
    {
        return [
            $user->username,
            $user->name,
            $user->email,
            (int) $user->user_status === 1 ? 'Kích hoạt' : 'Vô hiệu',
            Carbon::parse($user->created_at)->format('d-m-Y'),
        ];
    }
}
