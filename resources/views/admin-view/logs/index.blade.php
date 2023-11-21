@extends('admin-view.layouts.app')
@section('content')
    <style>
        @media (min-width: 768px) {
            .modal-dialog {
                width: 800px;
                margin: 30px auto;
            }
        }
        .max{
            max-width: 90%;
            width: 90%;
            word-break: break-all;
        }
    </style>
    <div class="container-fluid">
        <ul class="nav nav-tabs p-b">
            <li class="pull-right custom-search">
                <form class="form-inline" action="/admin/logs" method="get">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" value="{{request()->search}}"
                            placeholder="Search logs...">
                        <span class="input-group-btn search-select-wrap">
                            <select class="form-control search-select" name="search_type">
                                @php $level = ['', 'error', 'notice', 'info', 'debug', 'warning', 'critical', 'alert', 'emergency'] @endphp
                                @foreach($level as $val)
                                <option value="{{$val}}" {{request()->search_type == $val ? 'selected' : ''}}>{{$val}}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-default"><span class="fa fa-search"
                                    aria-hidden="true"></span></button>
                        </span>
                    </div>
                </form>
            </li>
        </ul>
        <table class="table">
            <thead>
                <tr>
                    <th class="p-l">ID</th>
                    <th class="max">Description</th>
                    <th>Creation Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @if (count($items))
                    @foreach ($items as $item)
                        <tr>
                            <td class="p-l">{{ $item->id }}</td>
                            <td class="max"><b>Description</b>:{!! $item->description !!} <br> <b>User_agent</b>: {{ $item->user_agent }}
                            </td>
                            <td nowrap=""><b>Type</b>: {{ $item->type }} <br> <b>Level</b>: {{ $item->level }}
                                <br>{{ $item->updated_at  }}</td>
                            <td class="service-block__action">
                                <ul>
                                    <li style="list-style: none;"><a href="#" data-toggle="modal"
                                            data-target="#modalDiv" data-action="log_desc"
                                            data-id="{{ $item->id }}">Details</a></li>
                                </ul>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="12">Chưa có dữ liệu</td>
                    </tr>
                @endif

            </tbody>
            @php $links = $items->withQueryString()->links('pagination::bootstrap-5') @endphp
            <div class="first-pagination">{!!$links!!}</div>
        </table>
        <div class="last-pagination">{!!$links!!}</div>
    </div>
@endsection
