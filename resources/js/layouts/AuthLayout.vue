<template>
	<!--begin::Authentication Layout -->
	<div class="d-flex flex-column flex-column-fluid flex-lg-row justify-content-center"
		:style="`background-image: url('${setting?.bg_auth}'); background-size: cover; background-position: center; background-repeat: no-repeat; height: 100vh;`">
		<!--begin::Body-->
		<div class="d-flex flex-column-fluid flex-lg-row-left-content-left-content-lg-end p-12 p-lg-20" style="height: 100%">
			<!--begin::Card-->
			<div class="bg-body-black d-flex flex-column align-items-stretch flex-center rounded-4 w-md-600px p-md-20 w-100" style="max-height: 100%; margin-top: 30px;">
				<!--begin::Wrapper-->
				<div class="d-flex flex-center flex-column flex-column-fluid px-5 py-5 py-md-0">
					<router-view></router-view>
				</div>
				<!--end::Wrapper-->
			</div>
			<!--end::Card-->
		</div>
		<!--end::Body-->
	</div>
	<!--end::Authentication Layout -->
</template>


<script lang="ts">
import { getAssetPath } from "@/core/helpers/assets";
import { defineComponent, onMounted } from "vue";
import LayoutService from "@/core/services/LayoutService";
import { useBodyStore } from "@/stores/body";
import { useSetting } from "@/services";

export default defineComponent({
	name: "auth-layout",
	components: {},
	setup() {
		const store = useBodyStore();
		const { data: setting = {} } = useSetting()

		onMounted(() => {
			LayoutService.emptyElementClassesAndAttributes(document.body);

			store.addBodyClassname("app-blank");
			store.addBodyClassname("bg-body");
		});

		return {
			getAssetPath,
			setting
		};
	},
});
</script>
