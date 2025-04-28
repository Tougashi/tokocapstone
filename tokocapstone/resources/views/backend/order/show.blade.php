@extends('backend.layouts.master')

@section('title','Detail Pesanan')

@section('main-content')
<div class="card">
<h5 class="card-header">Pesanan       <a href="{{route('order.pdf',$order->id)}}" class=" btn btn-sm btn-primary shadow-sm float-right"><i class="fas fa-download fa-sm text-white-50"></i> Generate PDF</a>
  </h5>
  <div class="card-body">
    @if($order)
    <table class="table table-striped table-hover">
      <thead>
        <tr>
            <th>No.</th>
            <th>No. Pesanan</th>
            <th>Nama</th>
            <th>Email</th>
            <th>Jumlah</th>
            <th>Biaya Pengiriman</th>
            <th>Total</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <tr>
            <td>{{$order->id}}</td>
            <td>{{$order->order_number}}</td>
            <td>{{$order->first_name}} {{$order->last_name}}</td>
            <td>{{$order->email}}</td>
            <td>{{$order->quantity}}</td>
            <td>Rp {{number_format($order->shipping->price, 0, ',', '.')}}</td>
            <td>Rp {{number_format($order->total_amount, 0, ',', '.')}}</td>
            <td>
                @if($order->status=='new')
                  <span class="badge badge-primary">Baru</span>
                @elseif($order->status=='process')
                  <span class="badge badge-warning">Diproses</span>
                @elseif($order->status=='delivered')
                  <span class="badge badge-success">Terkirim</span>
                @else
                  <span class="badge badge-danger">Dibatalkan</span>
                @endif
            </td>
            <td>
                <a href="{{route('order.edit',$order->id)}}" class="btn btn-primary btn-sm float-left mr-1" style="height:30px; width:30px;border-radius:50%" data-toggle="tooltip" title="edit" data-placement="bottom"><i class="fas fa-edit"></i></a>
                <form method="POST" action="{{route('order.destroy',[$order->id])}}">
                  @csrf
                  @method('delete')
                      <button class="btn btn-danger btn-sm dltBtn" data-id={{$order->id}} style="height:30px; width:30px;border-radius:50%" data-toggle="tooltip" data-placement="bottom" title="Hapus"><i class="fas fa-trash-alt"></i></button>
                </form>
            </td>
        </tr>
      </tbody>
    </table>

    <section class="confirmation_part section_padding">
      <div class="order_boxes">
        <div class="row">
          <div class="col-lg-6 col-lx-4">
            <div class="order-info">
              <h4 class="text-center pb-4">INFORMASI PESANAN</h4>
              <table class="table">
                    <tr class="">
                        <td>Nomor Pesanan</td>
                        <td> : {{$order->order_number}}</td>
                    </tr>
                    <tr>
                        <td>Tanggal Pesanan</td>
                        <td> : {{$order->created_at->format('D d M, Y')}} jam {{$order->created_at->format('H:i')}} </td>
                    </tr>
                    <tr>
                        <td>Jumlah</td>
                        <td> : {{$order->quantity}}</td>
                    </tr>
                    <tr>
                        <td>Status Pesanan</td>
                        <td> : {{$order->status == 'new' ? 'Baru' : ($order->status == 'process' ? 'Diproses' : ($order->status == 'delivered' ? 'Terkirim' : 'Dibatalkan'))}}</td>
                    </tr>
                    <tr>
                        <td>Biaya Pengiriman</td>
                        <td> : Rp {{number_format($order->shipping->price, 0, ',', '.')}}</td>
                    </tr>
                    <tr>
                      <td>Kupon</td>
                      <td> : Rp {{number_format($order->coupon, 0, ',', '.')}}</td>
                    </tr>
                    <tr>
                        <td>Total</td>
                        <td> : Rp {{number_format($order->total_amount, 0, ',', '.')}}</td>
                    </tr>
                    <tr>
                        <td>Metode Pembayaran</td>
                        <td> : @if($order->payment_method=='cod') Bayar di Tempat @else Paypal @endif</td>
                    </tr>
                    <tr>
                        <td>Status Pembayaran</td>
                        <td> : {{$order->payment_status == 'paid' ? 'Lunas' : 'Belum Dibayar'}}</td>
                    </tr>
              </table>
            </div>
          </div>

          <div class="col-lg-6 col-lx-4">
            <div class="shipping-info">
              <h4 class="text-center pb-4">INFORMASI PENGIRIMAN</h4>
              <table class="table">
                    <tr class="">
                        <td>Nama Lengkap</td>
                        <td> : {{$order->first_name}} {{$order->last_name}}</td>
                    </tr>
                    <tr>
                        <td>Email</td>
                        <td> : {{$order->email}}</td>
                    </tr>
                    <tr>
                        <td>No. Telepon</td>
                        <td> : {{$order->phone}}</td>
                    </tr>
                    <tr>
                        <td>Alamat</td>
                        <td> : {{$order->address1}}, {{$order->address2}}</td>
                    </tr>
                    <tr>
                        <td>Negara</td>
                        <td> : {{$order->country}}</td>
                    </tr>
                    <tr>
                        <td>Kode Pos</td>
                        <td> : {{$order->post_code}}</td>
                    </tr>
              </table>
            </div>
          </div>
        </div>
      </div>
    </section>
    @endif

  </div>
</div>
@endsection

@push('styles')
<style>
    .order-info,.shipping-info{
        background:#ECECEC;
        padding:20px;
    }
    .order-info h4,.shipping-info h4{
        text-decoration: underline;
    }

</style>
@endpush
