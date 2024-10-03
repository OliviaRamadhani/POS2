<script setup lang="ts">
import { block, unblock } from "@/libs/utils";
import { onMounted, ref, watch } from "vue";
import * as Yup from "yup";
import axios from "@/libs/axios";
import { toast } from "vue3-toastify";
import ApiService from "@/core/services/ApiService";
import type { Product } from "@/types/pos";

// Props untuk menerima produk terpilih dari komponen induk
const props = defineProps({
    selected: {
        type: Number,
        default: null,
    },
});

// Emit event close dan refresh ke komponen induk
const emit = defineEmits(["close", "refresh"]);

// Menginisialisasi data form dan referensi form
const formData = ref<Product>({} as Product);
const imagePreview = ref<string | null>(null);
const formRef = ref();
const photo = ref<any>([]);
const fileTypes = ref(["image/jpeg", "image/png", "image/jpg"]);

// Skema validasi menggunakan Yup
const formSchema = Yup.object().shape({
    name: Yup.string().required("Nama Harus Diisi"),
    price: Yup.number().required("Harga Harus Diisi").positive("Harga Harus Positif"),
    quantity: Yup.number().required("Kuantitas harus diisi").integer("Jumlah Harus Diisi Angka").min(0, "Kuantitas minimal 0"),
    description: Yup.string(),
    category: Yup.string().required("Kategori Diperlukan"),
});

// Fungsi untuk mendapatkan data produk yang akan diedit
function getEdit() {
    block(document.getElementById("form-produk"));
    ApiService.get(`/inventori/produk/${props.selected}`)
        .then(({ data }) => {
            formData.value = data.produk;
            if (data.product.image_url) {
                toast.info("Retrieving existing photo")
                imagePreview.value = data.produk.image_url;
                photo.value = [data.produk.image_url];
            } else {
                toast.info("There's no existing photo")
                photo.value = [];
            }
        })
        .catch((err: any) => {
            toast.error(err.response.data.message);
        })
        .finally(() => {
            unblock(document.getElementById("form-produk"));
        });
}

// Fungsi untuk menangani perubahan input gambar
function handleImageChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target?.result as string;
            photo.value = [file];
        };
        reader.readAsDataURL(file);
    }
}

// Fungsi untuk submit form produk
function submit() {
    block(document.getElementById("form-produk"));
    const formDataToSubmit = new FormData();
    formDataToSubmit.append("name", formData.value.name);
    formDataToSubmit.append("category", formData.value.category);
    formDataToSubmit.append("price", Math.floor(formData.value.price).toString());
    formDataToSubmit.append("quantity", formData.value.quantity.toString());
    formDataToSubmit.append("description", formData.value.description || '');

    if (photo.value && photo.value.length > 0) {
        formDataToSubmit.append("image_url", photo.value[0].file);
    }

    const apiEndpoint = props.selected
        ? `/inventori/produk/${props.selected}`
        : "/inventori/produk/store";

    const method = "post";

    axios({
            method: method,
            url: apiEndpoint,
            data: formDataToSubmit,
            headers: {
                    "Content-Type": "multipart/form-data",
                    },
                })
                .then(() => {
                    emit("refresh");
                    emit("close");
                    toast.success("Produk berhasil disimpan");
                    formRef.value.resetForm();
                })
                .catch((err: any) => {
                    formRef.value.setErrors(err.response.data.errors);
                    toast.error(err.response.data.message);
                })
                .finally(() => {
                    unblock(document.getElementById("form-produk"));
                });
            }

            onMounted(() => {
                if (props.selected) {
                    getEdit();
                }
            });

            watch(
                () => props.selected,
                () => {
                    if (props.selected) {
                        toast.info("Toas")
                        getEdit();
                    }
                }
            );
</script>

<template>
    <VForm class="form card mb-10" @submit="submit" :validation-schema="formSchema" id="form-produk" ref="formRef">
        <div class="card-header align-items-center">
            <h2 class="mb-0">{{ selected ? "Edit" : "Add" }} Product</h2>
            <button type="button" class="btn btn-sm btn-light-danger ms-auto" @click="emit('close')">
                Cancel <i class="la la-times-circle p-0"></i>
            </button>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- Field Name -->
                <div class="col-md-6">
                    <div class="fv-row mb-7">
                        <label class="form-label fw-bold fs-6 required" for="produk-name">
                            Product Name
                        </label>
                        <Field class="form-control form-control-lg form-control-solid" 
                               type="text" name="name" autocomplete="off" 
                               v-model="formData.name" id="produk-name" 
                               placeholder="Enter Product Name" />
                        <div class="fv-plugins-message-container">
                            <div class="fv-help-block">
                                <ErrorMessage name="name" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Field Category -->
                <div class="col-md-6">
                    <div class="fv-row mb-7">
                        <label class="form-label fw-bold fs-6 required" for="produk-category">
                            Category
                        </label>
                        <Field as="select" class="form-control form-control-lg form-control-solid" 
                               name="category" v-model="formData.category" 
                               id="produk-category">
                            <option value="" disabled hidden>Select Category</option>
                            <option value="makanan">Makanan</option>
                            <option value="dessert">Dessert</option>
                            <option value="minuman">Minuman</option>
                        </Field>
                        <div class="fv-plugins-message-container">
                            <div class="fv-help-block">
                                <ErrorMessage name="category" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Field Price -->
                <div class="col-md-6">
                    <div class="fv-row mb-7">
                        <label class="form-label fw-bold fs-6 required" for="produk-price">
                            Price
                        </label>
                        <Field class="form-control form-control-lg form-control-solid" 
                               type="number" name="price" autocomplete="off" 
                               v-model="formData.price" id="produk-price" 
                               placeholder="Enter Price" />
                        <div class="fv-plugins-message-container">
                            <div class="fv-help-block">
                                <ErrorMessage name="price" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Field Quantity -->
                <div class="col-md-6">
                    <div class="fv-row mb-7">
                        <label class="form-label fw-bold fs-6 required" for="produk-quantity">
                            Quantity
                        </label>
                        <Field class="form-control form-control-lg form-control-solid" 
                               type="number" name="quantity" autocomplete="off" 
                               v-model="formData.quantity" id="produk-quantity" 
                               placeholder="Enter Quantity" />
                        <div class="fv-plugins-message-container">
                            <div class="fv-help-block">
                                <ErrorMessage name="quantity" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Field Description -->
                <div class="col-md-6">
                    <div class="fv-row mb-7">
                        <label class="form-label fw-bold fs-6" for="produk-description">
                            Description
                        </label>
                        <Field class="form-control form-control-lg form-control-solid" 
                               type="text" name="description" autocomplete="off" 
                               v-model="formData.description" id="produk-description" 
                               placeholder="Enter Description" />
                        <div class="fv-plugins-message-container">
                            <div class="fv-help-block">
                                <ErrorMessage name="description" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Field Image -->
                <div class="col-md-6">
                    <div class="fv-row mb-7">
                        <label class="form-label fw-bold fs-6" for="produk-image">
                            Image
                        </label>
                        <file-upload
                            :files="photo"
                            :accepted-file-types="fileTypes"
                            required
                            v-on:updatefiles="(file: File[]) => (photo = file)"
                        ></file-upload>
                        <div v-if="imagePreview" class="mt-3">
                            <img :src="imagePreview" alt="Image Preview" class="img-thumbnail" style="max-width: 200px;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary me-3">
                Save Product
            </button>
            <button type="button" class="btn btn-light" @click="emit('close')">
                Cancel
            </button>
 </div>
    </VForm>
</template>