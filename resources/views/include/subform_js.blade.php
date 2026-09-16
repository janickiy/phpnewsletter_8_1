<script>
    $(document).ready(function () {
        $.ajax({
            url: "{{ route('frontend.categories') }}",
            method: "get",
            dataType: "json",
            success: function (data) {
                $.each(data.items, function (key, item) {
                    let checkBox = ''
                        + '<div class="form-check">'
                        + '<label class="form-check-label">'
                        + '<input checked="checked" name="categoryId[]" type="checkbox" value="' + item.id + '"> '
                        + item.name
                        + '</label>'
                        + '</div>';

                    $(checkBox).prependTo('#addsub');
                });
            }
        });

        $(document).on("click", "#sub", function () {
            let arr = $("#addsub").serializeArray();
            let aParams = [];
            let sParam;

            for (var i = 0, count = arr.length; i < count; i++) {
                sParam = encodeURIComponent(arr[i].name);
                sParam += "=";
                sParam += encodeURIComponent(arr[i].value);
                aParams.push(sParam);
            }

            sParam = 'action';
            aParams.push(sParam);

            let sendData = aParams.join("&");

            $.ajax({
                url: "{{ route('frontend.addsub') }}",
                method: "POST",
                data: sendData,
                dataType: "json",
                success: function (data) {
                    if (data.result != null) {
                        let alert_msg = '';

                        if (data.result == 'success') {
                            alert_msg += '<div class="alert alert-success alert-dismissable">';
                            alert_msg += '<button class="close" aria-hidden="true" data-dismiss="alert" type="button">×</button>';
                            alert_msg += data.msg;
                            alert_msg += '</div>';
                        } else if (data.result == 'errors') {
                            $.each(data.msg, function (index, val) {
                                alert_msg += '<div class="alert alert-danger alert-dismissable">';
                                alert_msg += '<button class="close" aria-hidden="true" data-dismiss="alert" type="button">×</button>';

                                let arr = data.msg[index];
                                alert_msg += '<ul>';

                                for (var i = 0; i < arr.length; i++) {
                                    alert_msg += '<li>' + arr[i] + '</li>';
                                }

                                alert_msg += '</ul>';
                                alert_msg += '</div>';
                            });
                        }

                        $("#resultSub").html(alert_msg);
                    }
                }
            });
        });
    });

</script>
