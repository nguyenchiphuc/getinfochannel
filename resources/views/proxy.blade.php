<form action="{{route('proxy')}}" method="post">
  @csrf
  <label for="proxy">Proxy:</label><br>
  <textarea name="proxy" id="proxy" cols="30" rows="10">{{$info->proxy}}</textarea> <br>
  <label for="token">Token:</label><br>
  <textarea name="token" id="token" cols="30" rows="10">{{$info->token}}</textarea><br><br>
  <input type="submit" value="Submit">
</form> 