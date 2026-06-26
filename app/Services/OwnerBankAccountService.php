<?php

namespace App\Services;

use App\Models\OwnerBankAccount;
use Illuminate\Http\UploadedFile;

class OwnerBankAccountService
{
    public function __construct(
        private FileUploadService $fileUploadService
    ) {}

    /**
     * Tạo/cập nhật bank account
     */
    public function saveBankAccount(
        int $ownerId,
        array $data,
        ?UploadedFile $qrImage = null
    )
    {
        /*
        |--------------------------------------------------------------------------
        | Find existing
        |--------------------------------------------------------------------------
        */

        $bankAccount = OwnerBankAccount::query()

            ->where(
                'owner_id',
                $ownerId
            )

            ->first();

        /*
        |--------------------------------------------------------------------------
        | Upload QR image
        |--------------------------------------------------------------------------
        */

        if ($qrImage) {

            /*
            |--------------------------------------------------------------------------
            | Delete old image
            |--------------------------------------------------------------------------
            */

            if ($bankAccount?->qr_image) {

                $this->fileUploadService
                    ->delete(
                        $bankAccount->qr_image
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | Upload new image
            |--------------------------------------------------------------------------
            */

            $data['qr_image'] =
                $this->fileUploadService
                    ->upload(

                        $qrImage,

                        'bank-accounts'
                    );
        }

        /*
        |--------------------------------------------------------------------------
        | Create
        |--------------------------------------------------------------------------
        */

        if (!$bankAccount) {

            $bankAccount =
                OwnerBankAccount::create([

                    'owner_id' => $ownerId,

                    ...$data
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Update
        |--------------------------------------------------------------------------
        */

        else {

            $bankAccount->update($data);

            $bankAccount->refresh();
        }

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        |
        | Trả đúng dữ liệu như DB
        |
        */

        return $bankAccount;
    }

    /**
 * Lấy bank account của owner
 */
public function getMyBankAccount(
    int $ownerId
)
{
    $bankAccount = OwnerBankAccount::query()

        ->where(
            'owner_id',
            $ownerId
        )

        ->first();

    /*
    |--------------------------------------------------------------------------
    | Không có bank account
    |--------------------------------------------------------------------------
    */

    if (!$bankAccount) {

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Append qr url
    |--------------------------------------------------------------------------
    */

    return [

        ...$bankAccount->toArray(),

        'qr_image_url' => $this
            ->fileUploadService
            ->url(
                $bankAccount->qr_image
            )
    ];
}

/**
 * Admin xem bank account của owner
 */
public function getOwnerBankAccount(
    int $ownerId
)
{
    $bankAccount = OwnerBankAccount::query()

        ->with([
            'owner:id,name,email'
        ])

        ->where(
            'owner_id',
            $ownerId
        )

        ->first();

    /*
    |--------------------------------------------------------------------------
    | Không có bank account
    |--------------------------------------------------------------------------
    */

    if (!$bankAccount) {

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Return
    |--------------------------------------------------------------------------
    */

    return [

        ...$bankAccount->toArray(),

        'qr_image_url' => $this
            ->fileUploadService
            ->url(
                $bankAccount->qr_image
            )
    ];
}
}