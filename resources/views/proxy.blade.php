<form action="{{route('proxy')}}" method="post">
  @csrf
  <label for="proxy">Proxy:</label><br>
  <textarea name="proxys" id="proxys" cols="30" rows="10">{{$info->proxys}}</textarea> <br>
  <label for="token">Token:</label><br>
  <textarea name="token" id="token" cols="30" rows="10">{{$info->token}}</textarea><br><br>
  <label for="token">using_proxy:</label><br>
  <input type="number" name="using_proxy" value="{{$info->using_proxy}}" min="0" max="1">
  <input type="submit" value="Submit">
</form> 