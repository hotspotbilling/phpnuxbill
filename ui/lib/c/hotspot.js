        $(document).on("click", ".cdelete", function(e) {
            e.preventDefault();
			var id = this.id;
			bootbox.confirm("Are you sure?", function(result) {
				if(result){
					window.location.href = "index.php?_route=services/delete/" + id;
				}
			});
        });